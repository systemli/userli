<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Event\UserEvent;
use App\EventListener\RecoveryProcessListener;
use App\Sender\RecoveryProcessMessageSender;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RecoveryProcessListenerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = RecoveryProcessListener::getSubscribedEvents();

        self::assertArrayHasKey(UserEvent::RECOVERY_PROCESS_STARTED, $events);
        self::assertEquals('onRecoveryProcessStarted', $events[UserEvent::RECOVERY_PROCESS_STARTED]);
    }

    public function testOnRecoveryProcessStartedSendsMessageWithSessionLocale(): void
    {
        $user = new User('user@example.org');

        $session = $this->createStub(SessionInterface::class);
        $session->method('get')->with('_locale', 'en')->willReturn('fr');

        $request = $this->createStub(Request::class);
        $request->method('getSession')->willReturn($session);

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $sender = $this->createMock(RecoveryProcessMessageSender::class);
        $sender->expects($this->once())
            ->method('send')
            ->with($user, 'fr');

        $listener = new RecoveryProcessListener($requestStack, $sender, 'en');
        $listener->onRecoveryProcessStarted(new UserEvent($user));
    }

    public function testOnRecoveryProcessStartedUsesDefaultLocaleWhenSessionReturnsNull(): void
    {
        $user = new User('user@example.org');

        $session = $this->createStub(SessionInterface::class);
        $session->method('get')->with('_locale', 'en')->willReturn('en');

        $request = $this->createStub(Request::class);
        $request->method('getSession')->willReturn($session);

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $sender = $this->createMock(RecoveryProcessMessageSender::class);
        $sender->expects($this->once())
            ->method('send')
            ->with($user, 'en');

        $listener = new RecoveryProcessListener($requestStack, $sender, 'en');
        $listener->onRecoveryProcessStarted(new UserEvent($user));
    }
}
