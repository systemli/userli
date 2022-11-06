<?php

namespace App\EventListener;

use App\Entity\User;
use App\Event\LoginEvent;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginListener implements EventSubscriberInterface
{
    private EntityManagerInterface $manager;
    private PasswordUpdater $passwordUpdater;

    /**
     * LoginListener constructor.
     */
    public function __construct(EntityManagerInterface $manager, PasswordUpdater $passwordUpdater)
    {
        $this->manager = $manager;
        $this->passwordUpdater = $passwordUpdater;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $request = $event->getRequest();
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();
        $user->setPlainPassword($request->get('_password'));
        $this->handleLogin($user);
    }

    public function onLogin(LoginEvent $event): void
    {
        $this->handleLogin($event->getUser());
    }

    private function handleLogin(User $user): void
    {
        // update password hash if necessary
        if (($user->getPasswordVersion() < User::CURRENT_PASSWORD_VERSION) && null !== $plainPassword = $user->getPlainPassword()) {
            $user->setPasswordVersion(User::CURRENT_PASSWORD_VERSION);
            $this->passwordUpdater->updatePassword($user, $plainPassword);
        }
        $this->updateLastLogin($user);
    }

    private function updateLastLogin(User $user): void
    {
        $user->updateLastLoginTime();
        $this->manager->persist($user);
        $this->manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
            LoginEvent::class => 'onLogin',
        ];
    }
}
