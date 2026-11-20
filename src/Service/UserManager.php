<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PaginatedResult;
use App\Entity\Alias;
use App\Entity\AppPassword;
use App\Entity\Domain;
use App\Entity\User;
use App\Enum\MailCrypt;
use App\Enum\Roles;
use App\Enum\UserNotificationType;
use App\Event\UserEvent;
use App\Exception\AliasConversionException;
use App\Form\Model\UserAdminModel;
use App\Handler\DeleteHandler;
use App\Handler\MailCryptKeyHandler;
use App\Helper\PasswordUpdater;
use App\Repository\AliasRepository;
use App\Repository\OpenPgpKeyRepository;
use App\Repository\UserNotificationRepository;
use App\Repository\UserRepository;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class UserManager
{
    private const int PAGE_SIZE = 20;

    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $repository,
        private PasswordUpdater $passwordUpdater,
        private MailCryptKeyHandler $mailCryptKeyHandler,
        private SettingsService $settingsService,
        private DomainGuesser $domainGuesser,
        private UserResetService $userResetService,
        private DeleteHandler $deleteHandler,
        private AliasRepository $aliasRepository,
        private VoucherRepository $voucherRepository,
        private OpenPgpKeyRepository $openPgpKeyRepository,
        private UserNotificationRepository $userNotificationRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Find users with offset-based pagination and optional filters.
     *
     * @return PaginatedResult<User>
     */
    public function findPaginated(int $page = 1, string $search = '', ?Domain $domain = null, string $deleted = 'active', string $role = '', string $mailCrypt = '', string $twofactor = ''): PaginatedResult
    {
        return PaginatedResult::paginate(
            $page,
            self::PAGE_SIZE,
            fn (): int => $this->repository->countByFilters($search, $domain, $deleted, $role, $mailCrypt, $twofactor),
            fn (int $limit, int $offset): array => $this->repository->findPaginatedByFilters($search, $domain, $deleted, $role, $mailCrypt, $twofactor, $limit, $offset),
        );
    }

    /**
     * Create a new user from the admin form model.
     */
    public function create(UserAdminModel $model): User
    {
        $user = $this->buildUser($model);

        $this->em->persist($user);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new UserEvent($user), UserEvent::USER_CREATED);

        return $user;
    }

    /**
     * Convert an alias into a new user with the alias address.
     *
     * The alias is deleted. An OpenPGP key for the address is kept, so the new
     * user can manage it and WKD keeps publishing it.
     *
     * @throws AliasConversionException
     */
    public function createFromAlias(Alias $alias, string $plainPassword): User
    {
        $this->assertConvertible($alias);

        $model = new UserAdminModel();
        $model->setEmail((string) $alias->getSource());
        $model->setPlainPassword($plainPassword);
        $model->setRoles([Roles::USER]);

        $user = $this->em->wrapInTransaction(function () use ($model, $alias): User {
            $user = $this->buildUser($model);
            $this->em->persist($user);

            // Create the user before deleting the alias: the deletion removes the
            // OpenPGP key of the address unless a user or alias still owns it.
            // The flush in deleteAlias() writes the new user as well.
            $this->deleteHandler->deleteAlias($alias);

            return $user;
        });

        $this->eventDispatcher->dispatch(new UserEvent($user), UserEvent::USER_CREATED);

        return $user;
    }

    /**
     * Check whether an alias can be converted into a user.
     *
     * @throws AliasConversionException
     */
    public function assertConvertible(Alias $alias): void
    {
        $source = (string) $alias->getSource();

        if ($alias->isDeleted()) {
            throw AliasConversionException::aliasDeleted($source);
        }

        if ($alias->isRandom()) {
            throw AliasConversionException::aliasRandom($source);
        }

        if (null === $this->domainGuesser->guess($source)) {
            throw AliasConversionException::domainNotFound($source);
        }

        // Includes deleted users, as their addresses stay blocked
        if (null !== $this->repository->findByEmail($source)) {
            throw AliasConversionException::userExists($source);
        }
    }

    /**
     * Build a new user from the admin form model without persisting it.
     */
    private function buildUser(UserAdminModel $model): User
    {
        $user = new User($model->getEmail() ?? '');

        $roles = $model->getRoles();
        if (!in_array(Roles::USER, $roles, true)) {
            $roles[] = Roles::USER;
        }

        $user->setRoles($roles);

        $user->setQuota($model->getQuota());
        $user->setSmtpQuotaLimits($model->getSmtpQuotaLimits());
        $user->setPasswordChangeRequired(true);

        $plainPassword = $model->getPlainPassword() ?? '';
        $this->passwordUpdater->updatePassword($user, $plainPassword);

        $mailCrypt = MailCrypt::from($this->settingsService->get('mail_crypt'));
        $this->mailCryptKeyHandler->create($user, $plainPassword, $mailCrypt->isAtLeast(MailCrypt::ENABLED_ENFORCE_NEW_USERS));

        $domain = $this->domainGuesser->guess($user->getEmail());
        if (null !== $domain) {
            $user->setDomain($domain);
        }

        return $user;
    }

    /**
     * Update an existing user from the admin form model.
     *
     * Returns a recovery token string if the password was changed and MailCrypt is active,
     * or null otherwise.
     */
    public function update(User $user, UserAdminModel $model): ?string
    {
        $recoveryToken = null;

        $user->setRoles($model->getRoles());
        $user->setQuota($model->getQuota());
        $user->setSmtpQuotaLimits($model->getSmtpQuotaLimits());
        $user->setPasswordChangeRequired($model->isPasswordChangeRequired());

        $plainPassword = $model->getPlainPassword();
        if (!empty($plainPassword)) {
            if ($user->hasMailCryptSecretBox()) {
                $recoveryToken = $this->userResetService->resetUser($user, $plainPassword);
            } else {
                $this->passwordUpdater->updatePassword($user, $plainPassword);
                $this->em->getRepository(AppPassword::class)->removeByUser($user);
            }

            $user->setPasswordChangeRequired(true);
        }

        // Deactivate 2FA if checkbox was unchecked
        if (!$model->isTotpConfirmed() && $user->isTotpAuthenticationEnabled()) {
            $user->setTotpSecret(null);
            $user->setTotpConfirmed(false);
            $user->setTotpBackupCodes([]);
        }

        $this->em->flush();

        return $recoveryToken;
    }

    /**
     * Soft-delete a user.
     */
    public function delete(User $user): void
    {
        $this->deleteHandler->deleteUser($user);
    }

    /**
     * @return array{aliases: int, vouchers: int, vouchers_redeemed: int, openpgp_keys: int, app_passwords: int, password_compromised: bool}
     */
    public function getUserStats(User $user): array
    {
        return [
            'aliases' => count($this->aliasRepository->findByUser($user)),
            'vouchers' => $this->voucherRepository->countVouchersByUser($user, null),
            'vouchers_redeemed' => $this->voucherRepository->countVouchersByUser($user, true),
            'openpgp_keys' => count($this->openPgpKeyRepository->findByUploader($user)),
            'app_passwords' => $this->em->getRepository(AppPassword::class)->count(['user' => $user, 'expiresAt' => null]),
            'password_compromised' => $this->userNotificationRepository->hasRecentNotification($user, UserNotificationType::PASSWORD_COMPROMISED, 24 * 365),
        ];
    }
}
