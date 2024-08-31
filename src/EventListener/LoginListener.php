<?php

namespace App\EventListener;

use App\Entity\User;
use App\Event\LoginEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

readonly class LoginListener implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $manager)
    {
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof User) {
            $this->handleLogin($user);
        }
    }

    public function onLogin(LoginEvent $event): void
    {
        $this->handleLogin($event->getUser());
    }

    private function handleLogin(User $user): void
    {
        $user->updateLastLoginTime();
        $this->manager->persist($user);
        $this->manager->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
            LoginEvent::NAME => 'onLogin',
        ];
    }
}
