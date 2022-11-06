<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\HttpUtils;

class LogoutListener implements EventSubscriberInterface
{
    public function onLogoutSuccess(LogoutEvent $event): void
    {
        $event->getRequest()->getSession()->getFlashBag()->add('success', 'flashes.logout-successful');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogoutSuccess',
        ];
    }
}
