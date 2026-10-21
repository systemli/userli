<?php

declare(strict_types=1);

namespace App\Handler;

use App\Entity\AppPassword;
use App\Entity\User;
use App\Event\LoginEvent;
use App\Service\AppPasswordManager;
use App\Service\SettingsService;
use App\Service\UserLastLoginUpdateService;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class UserAuthenticationHandler
{
    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
        private EventDispatcherInterface $eventDispatcher,
        private AppPasswordManager $appPasswordManager,
        private UserLastLoginUpdateService $userLastLoginUpdateService,
        private SettingsService $settingsService,
    ) {
    }

    public function authenticate(User $user, string $password): ?User
    {
        $hasher = $this->passwordHasherFactory->getPasswordHasher($user);
        if (!$hasher->verify($user->getPassword(), $password)) {
            return null;
        }

        // Mail clients (the callers that allow app passwords) of 2FA users must not get by with the main password alone
        if ($allowAppPassword && $user->isTotpAuthenticationEnabled() && $this->settingsService->get('twofactor_app_passwords_required')) {
            return null;
        }

        $this->eventDispatcher->dispatch(
            new LoginEvent($user, $password),
            LoginEvent::NAME
        );

        return $user;
    }

    /**
     * Authenticates mail clients, which may also use app passwords.
     *
     * Returns the matched app password, the user if the main password matched, or null.
     */
    public function authenticateMailClient(User $user, string $password): AppPassword|User|null
    {
        // App passwords skip LoginEvent: it would create MailCrypt keys with them and run HIBP checks
        if (null !== $appPassword = $this->appPasswordManager->find($user, $password)) {
            $this->userLastLoginUpdateService->updateLastLogin($user, $appPassword);

            return $appPassword;
        }

        return $this->authenticate($user, $password);
    }
}
