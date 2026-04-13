<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PaginatedResult;
use App\Entity\Domain;
use App\Entity\User;
use App\Enum\MailCrypt;
use App\Enum\Roles;
use App\Enum\UserNotificationType;
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
    ) {
    }

    /**
     * Find users with offset-based pagination and optional filters.
     *
     * @return PaginatedResult<User>
     */
    public function findPaginated(int $page = 1, string $search = '', ?Domain $domain = null, string $deleted = 'active', string $role = '', string $mailCrypt = '', string $twofactor = ''): PaginatedResult
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $total = $this->repository->countByFilters($search, $domain, $deleted, $role, $mailCrypt, $twofactor);
        $totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $items = $this->repository->findPaginatedByFilters($search, $domain, $deleted, $role, $mailCrypt, $twofactor, self::PAGE_SIZE, $offset);

        return new PaginatedResult($items, $page, $totalPages, $total);
    }

    /**
     * Create a new user from the admin form model.
     */
    public function create(UserAdminModel $model): User
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

        $this->em->persist($user);
        $this->em->flush();

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
     * @return array{aliases: int, vouchers: int, vouchers_redeemed: int, openpgp_keys: int, password_compromised: bool}
     */
    public function getUserStats(User $user): array
    {
        return [
            'aliases' => count($this->aliasRepository->findByUser($user)),
            'vouchers' => $this->voucherRepository->countVouchersByUser($user, null),
            'vouchers_redeemed' => $this->voucherRepository->countVouchersByUser($user, true),
            'openpgp_keys' => count($this->openPgpKeyRepository->findByUploader($user)),
            'password_compromised' => $this->userNotificationRepository->hasRecentNotification($user, UserNotificationType::PASSWORD_COMPROMISED, 24 * 365),
        ];
    }
}
