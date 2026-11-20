<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Event\UserEvent;
use App\EventListener\RecoveryProcessListener;
use App\Message\RecoveryProcessMail;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class RecoveryProcessListenerTest extends TestCase
{
    use EventListenerAttributes;

    public function testListensToEvents(): void
    {
        self::assertEquals([
            UserEvent::RECOVERY_PROCESS_STARTED => ['onRecoveryProcessStarted', 0],
        ], self::listenedEvents(RecoveryProcessListener::class));
    }

    public function testOnRecoveryProcessStartedDispatchesMailWithSessionLocale(): void
    {
        $listener = new RecoveryProcessListener($this->createRequestStack('fr'), $this->expectDispatch('fr'), 'en');
        $listener->onRecoveryProcessStarted(new UserEvent(new User('user@example.org')));
    }

    public function testOnRecoveryProcessStartedFallsBackToDefaultLocale(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('get')->with('_locale', 'en')->willReturnArgument(1);

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $listener = new RecoveryProcessListener($requestStack, $this->expectDispatch('en'), 'en');
        $listener->onRecoveryProcessStarted(new UserEvent(new User('user@example.org')));
    }

    private function createRequestStack(string $locale): RequestStack
    {
        $session = $this->createStub(SessionInterface::class);
        $session->method('get')->willReturn($locale);

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        return $requestStack;
    }

    private function expectDispatch(string $locale): MessageBusInterface
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn ($message): bool => $message instanceof RecoveryProcessMail
                && 'user@example.org' === $message->email
                && $locale === $message->locale))
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));

        return $bus;
    }
}
