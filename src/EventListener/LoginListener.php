<?php

namespace App\EventListener;

use App\Entity\User;
use App\Enum\MailCrypt;
use App\Event\LoginEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use App\Handler\MailCryptKeyHandler;

readonly class LoginListener implements EventSubscriberInterface
{
    private readonly MailCrypt $mailCrypt;

    public function __construct(
        private EntityManagerInterface $manager,
        private readonly MailCryptKeyHandler $mailCryptKeyHandler,
        private readonly int $mailCryptEnv,
    ) {
        $this->mailCrypt = MailCrypt::from($this->mailCryptEnv);
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof User) {
            $password = $event->getRequest()->get('_password');
            $this->handleLogin($user, $password);
        }
    }

    public function onAuthenticationHandlerSuccess(LoginEvent $event): void
    {
        $this->handleLogin($event->getUser(), $event->getPlainPassword());
    }

    private function handleLogin(User $user, ?string $password): void
    {
        if ($this->mailCrypt === MailCrypt::ENABLED_ENFORCE_ALL_USERS && null !== $password) {
            $this->enableMailCrypt($user, $password);
        }

        $this->updateLastLogin($user);
    }

    private function enableMailCrypt(User $user, string $password): void
    {
        if ($user->getMailCryptEnabled() || null !== $user->getMailCryptPublicKey()) {
            return;
        }
        $this->mailCryptKeyHandler->create($user, $password, true);
    }

    private function updateLastLogin(User $user): void
    {
        $user->updateLastLoginTime();
        $this->manager->persist($user);
        $this->manager->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
            LoginEvent::NAME => 'onAuthenticationHandlerSuccess',
        ];
    }
}
