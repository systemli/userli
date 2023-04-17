<?php

namespace App\EventListener;

use App\Entity\User;
use App\Event\LoginEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginListener implements EventSubscriberInterface
{
    private EntityManagerInterface $manager;

    /**
     * LoginListener constructor.
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $request = $event->getRequest();
        /** @var User|null $user */
        $user = $event->getAuthenticationToken()->getUser();
        if ($user !== null && $request->get('_password') !== null) {
            $this->handleLogin($user);
        }
    }

    public function onLogin(LoginEvent $event): void
    {
        $this->handleLogin($event->getUser());
    }

    private function handleLogin(User $user): void
    {
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
