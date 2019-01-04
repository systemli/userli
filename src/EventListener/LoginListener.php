<?php

namespace App\EventListener;

use App\Entity\User;
use App\Event\LoginEvent;
use App\Helper\PasswordUpdater;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * @author tim <tim@systemli.org>
 */
class LoginListener implements EventSubscriberInterface
{
    /**
     * @var ObjectManager
     */
    private $manager;
    /**
     * @var PasswordUpdater
     */
    private $passwordUpdater;

    /**
     * LoginListener constructor.
     *
     * @param ObjectManager   $manager
     * @param PasswordUpdater $passwordUpdater
     */
    public function __construct(ObjectManager $manager, PasswordUpdater $passwordUpdater)
    {
        $this->manager = $manager;
        $this->passwordUpdater = $passwordUpdater;
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $request = $event->getRequest();
        $user = $event->getAuthenticationToken()->getUser();
        $user->setPlainPassword($request->get('_password'));
        $this->handleLogin($user);
    }

    /**
     * @param LoginEvent $event
     */
    public function onLogin(LoginEvent $event)
    {
        $this->handleLogin($event->getUser());
    }

    /**
     * @param User $user
     */
    private function handleLogin(User $user)
    {
        // update password hash if necessary
        if ($user->getPasswordVersion() < User::CURRENT_PASSWORD_VERSION) {
            if (null !== $plainPassword = $user->getPlainPassword()) {
                $user->setPasswordVersion(User::CURRENT_PASSWORD_VERSION);
                $this->passwordUpdater->updatePassword($user, $plainPassword);
            }
        }
        $this->updateLastLogin($user);
    }

    /**
     * @param User $user
     */
    private function updateLastLogin(User $user)
    {
        $user->updateLastLoginTime();
        $this->manager->persist($user);
        $this->manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
            LoginEvent::NAME => 'onLogin', ];
    }
}
