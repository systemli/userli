<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PaginatedResult;
use App\Entity\User;
use App\Enum\MailCrypt;
use App\Enum\Roles;
use App\Form\Model\UserAdminModel;
use App\Handler\DeleteHandler;
use App\Handler\MailCryptKeyHandler;
use App\Helper\PasswordUpdater;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

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
    ) {
    }

    /**
     * Find users with offset-based pagination and optional filters.
     *
     * @return PaginatedResult<User>
     */
    public function findPaginated(int $page = 1, string $search = '', string $deleted = 'active', string $role = '', string $mailCrypt = '', string $twofactor = ''): PaginatedResult
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $total = $this->repository->countByFilters($search, $deleted, $role, $mailCrypt, $twofactor);
        $totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $items = $this->repository->findPaginatedByFilters($search, $deleted, $role, $mailCrypt, $twofactor, self::PAGE_SIZE, $offset);

        return new PaginatedResult($items, $page, $totalPages, $total);
    }

    /**
     * Create a new user from the admin form model.
     *
     * @param string[]|null $allowedRoles If set, only these roles may be assigned
     */
    public function create(UserAdminModel $model, ?array $allowedRoles = null): User
    {
        $user = new User($model->getEmail() ?? '');

        $roles = $model->getRoles();
        if (!in_array(Roles::USER, $roles, true)) {
            $roles[] = Roles::USER;
        }

        $this->assertRolesAllowed($roles, $allowedRoles);

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
     *
     * @param string[]|null $allowedRoles If set, only these roles may be assigned
     */
    public function update(User $user, UserAdminModel $model, ?array $allowedRoles = null): ?string
    {
        $recoveryToken = null;

        $this->assertRolesAllowed($model->getRoles(), $allowedRoles);

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
     * Validate that all given roles are within the allowed set.
     *
     * @param string[]      $roles        Roles to validate
     * @param string[]|null $allowedRoles Allowed roles (null = no restriction)
     *
     * @throws InvalidArgumentException if a disallowed role is found
     */
    private function assertRolesAllowed(array $roles, ?array $allowedRoles): void
    {
        if (null === $allowedRoles) {
            return;
        }

        foreach ($roles as $role) {
            if (!in_array($role, $allowedRoles, true)) {
                throw new InvalidArgumentException(sprintf('Role "%s" is not allowed.', $role));
            }
        }
    }
}
