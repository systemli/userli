<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Event\UserEvent;
use App\EventListener\RecoveryTokenCreatedListener;
use App\Message\RecoveryTokenCreatedMail;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class RecoveryTokenCreatedListenerTest extends TestCase
{
    use EventListenerAttributes;

    public function testListensToEvents(): void
    {
        self::assertEquals([
            UserEvent::RECOVERY_TOKEN_CREATED => ['onRecoveryTokenCreated', 0],
        ], self::listenedEvents(RecoveryTokenCreatedListener::class));
    }

    public function testDispatchesMailWithSessionLocale(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('get')->with('_locale', 'en')->willReturn('fr');

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn ($message): bool => $message instanceof RecoveryTokenCreatedMail
                && 'user@example.org' === $message->email
                && 'fr' === $message->locale))
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));

        $listener = new RecoveryTokenCreatedListener($requestStack, $bus, 'en');
        $listener->onRecoveryTokenCreated(new UserEvent(new User('user@example.org')));
    }
}
