<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Enum\MailCrypt;
use App\Event\LoginEvent;
use App\Handler\MailCryptKeyHandler;
use App\Service\SettingsService;
use App\Service\UserLastLoginUpdateService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

final readonly class LoginListener
{
    public function __construct(
        private UserLastLoginUpdateService $userLastLoginUpdateService,
        private MailCryptKeyHandler $mailCryptKeyHandler,
        private SettingsService $settingsService,
    ) {
    }

    #[AsEventListener(event: SecurityEvents::INTERACTIVE_LOGIN)]
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        // No password for programmatic logins, e.g. after a password recovery
        $password = $event->getRequest()->request->get('_password');

        $this->handleLogin($user, is_string($password) && '' !== $password ? $password : null);
    }

    #[AsEventListener(event: LoginEvent::NAME)]
    public function onAuthenticationHandlerSuccess(LoginEvent $event): void
    {
        $this->handleLogin($event->getUser(), $event->getPlainPassword());
    }

    private function handleLogin(User $user, ?string $password): void
    {
        $mailCrypt = MailCrypt::from($this->settingsService->get('mail_crypt'));
        if ($mailCrypt === MailCrypt::ENABLED_ENFORCE_ALL_USERS && null !== $password) {
            $this->enableMailCrypt($user, $password);
        }

        $this->userLastLoginUpdateService->updateLastLogin($user);
    }

    private function enableMailCrypt(User $user, string $password): void
    {
        if ($user->getMailCryptEnabled() || null !== $user->getMailCryptPublicKey()) {
            return;
        }

        $this->mailCryptKeyHandler->create($user, $password, true);
    }
}
