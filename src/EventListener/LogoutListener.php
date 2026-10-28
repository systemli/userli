<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final readonly class LogoutListener
{
    #[AsEventListener(event: LogoutEvent::class, priority: -1)]
    public function onLogoutSuccess(LogoutEvent $event): void
    {
        $session = $event->getRequest()->getSession();
        assert($session instanceof Session);
        $session->getFlashBag()->add('success', 'flashes.logout-successful');
    }
}
