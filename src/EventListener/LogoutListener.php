<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener implements EventSubscriberInterface
{
    public function onLogoutSuccess(LogoutEvent $event): void
    {
        $session = $event->getRequest()->getSession();
        assert($session instanceof Session);
        $session->getFlashBag()->add('success', 'flashes.logout-successful');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogoutSuccess',
        ];
    }
}
