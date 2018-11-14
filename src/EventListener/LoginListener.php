<?php

namespace App\EventListener;

use App\Entity\User;
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

    public function __construct(ObjectManager $manager, PasswordUpdater $passwordUpdater)
    {
        $this->manager = $manager;
        $this->passwordUpdater = $passwordUpdater;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $request = $event->getRequest();
        $user = $event->getAuthenticationToken()->getUser();

        if ($user->getPasswordVersion() < User::CURRENT_PASSWORD_VERSION) {
            $plainPassword = $request->get('_password');

            if (null !== $plainPassword) {
                $user->setPasswordVersion(User::CURRENT_PASSWORD_VERSION);
                $this->passwordUpdater->updatePassword($user, $plainPassword);
            }

            $this->manager->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
        );
    }
}
