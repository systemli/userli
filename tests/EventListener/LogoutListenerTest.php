<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\EventListener\LogoutListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListenerTest extends TestCase
{
    use EventListenerAttributes;

    public function testListensToEvents(): void
    {
        self::assertEquals([
            LogoutEvent::class => ['onLogoutSuccess', -1],
        ], self::listenedEvents(LogoutListener::class));
    }

    public function testOnLogoutSuccessAddsFlashMessage(): void
    {
        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with('success', 'flashes.logout-successful');

        $session = $this->createStub(Session::class);
        $session->method('getFlashBag')->willReturn($flashBag);

        $request = $this->createStub(Request::class);
        $request->method('getSession')->willReturn($session);

        $event = new LogoutEvent($request, null);

        $listener = new LogoutListener();
        $listener->onLogoutSuccess($event);
    }
}
