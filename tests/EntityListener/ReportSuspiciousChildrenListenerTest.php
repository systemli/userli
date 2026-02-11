<?php

declare(strict_types=1);

namespace App\Tests\EntityListener;

use App\Entity\User;
use App\EntityListener\ReportSuspiciousChildrenListener;
use App\Enum\Roles;
use App\Message\ReportSuspiciousChildren;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class ReportSuspiciousChildrenListenerTest extends TestCase
{
    public function testPreUpdateNoRoleChanges(): void
    {
        $user = new User('test@example.org');
        $args = $this->createStub(PreUpdateEventArgs::class);
        $args->method('getEntityChangeSet')
            ->willReturn(['someField' => [0, 1]]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())
            ->method('dispatch');

        $listener = new ReportSuspiciousChildrenListener($bus);
        $listener->preUpdate($user, $args);
    }

    public function testPreUpdateOtherRoleChanges(): void
    {
        $user = new User('test@example.org');
        $args = $this->createStub(PreUpdateEventArgs::class);
        $args->method('getEntityChangeSet')
            ->willReturn(['roles' => [[Roles::USER], [Roles::USER, Roles::PERMANENT]]]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())
            ->method('dispatch');

        $listener = new ReportSuspiciousChildrenListener($bus);
        $listener->preUpdate($user, $args);
    }

    public function testPreUpdateRoleSuspiciousRemoved(): void
    {
        $user = new User('test@example.org');
        $args = $this->createStub(PreUpdateEventArgs::class);
        $args->method('getEntityChangeSet')
            ->willReturn(['roles' => [[Roles::USER, Roles::SUSPICIOUS], [Roles::USER]]]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())
            ->method('dispatch');

        $listener = new ReportSuspiciousChildrenListener($bus);
        $listener->preUpdate($user, $args);
    }

    public function testPreUpdateRoleSuspiciousAdded(): void
    {
        $user = new User('user@example.org');
        $user->setId(42);
        $args = $this->createStub(PreUpdateEventArgs::class);
        $args->method('getEntityChangeSet')
            ->willReturn(['roles' => [[Roles::USER], [Roles::USER, Roles::SUSPICIOUS]]]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->with($this->callback(static function (ReportSuspiciousChildren $message): bool {
                return 42 === $message->userId;
            }))
            ->willReturn(new Envelope(new ReportSuspiciousChildren(42)));

        $listener = new ReportSuspiciousChildrenListener($bus);
        $listener->preUpdate($user, $args);
    }
}
