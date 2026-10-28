<?php

declare(strict_types=1);

namespace App\Tests\EntityListener;

use App\Entity\User;
use App\EntityListener\SpamUserNotificationListener;
use App\Enum\Roles;
use App\Message\SpamUserNotification;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class SpamUserNotificationListenerTest extends TestCase
{
    public function testPreUpdateNoRoleChanges(): void
    {
        $args = $this->createStub(PreUpdateEventArgs::class);
        $args->method('getEntityChangeSet')->willReturn(['someField' => [0, 1]]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        new SpamUserNotificationListener($bus)->preUpdate(new User('test@example.org'), $args);
    }

    public function testPreUpdateOtherRoleAdded(): void
    {
        $args = $this->createStub(PreUpdateEventArgs::class);
        $args->method('getEntityChangeSet')
            ->willReturn(['roles' => [[Roles::USER], [Roles::USER, Roles::SUSPICIOUS]]]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        new SpamUserNotificationListener($bus)->preUpdate(new User('test@example.org'), $args);
    }

    public function testPreUpdateSpamAlreadySet(): void
    {
        $args = $this->createStub(PreUpdateEventArgs::class);
        $args->method('getEntityChangeSet')
            ->willReturn(['roles' => [[Roles::SPAM], [Roles::SPAM, Roles::SUSPICIOUS]]]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        new SpamUserNotificationListener($bus)->preUpdate(new User('test@example.org'), $args);
    }

    public function testPreUpdateSpamRemoved(): void
    {
        $args = $this->createStub(PreUpdateEventArgs::class);
        $args->method('getEntityChangeSet')
            ->willReturn(['roles' => [[Roles::USER, Roles::SPAM], [Roles::USER]]]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        new SpamUserNotificationListener($bus)->preUpdate(new User('test@example.org'), $args);
    }

    public function testPreUpdateSpamAdded(): void
    {
        $user = new User('user@example.org');
        $user->setId(42);
        $args = $this->createStub(PreUpdateEventArgs::class);
        $args->method('getEntityChangeSet')
            ->willReturn(['roles' => [[Roles::USER], [Roles::USER, Roles::SPAM]]]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static fn (SpamUserNotification $message): bool => 42 === $message->userId))
            ->willReturn(new Envelope(new SpamUserNotification(42)));

        new SpamUserNotificationListener($bus)->preUpdate($user, $args);
    }
}
