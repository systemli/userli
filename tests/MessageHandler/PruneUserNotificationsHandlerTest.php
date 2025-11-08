<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\UserNotification;
use App\Message\PruneUserNotifications;
use App\MessageHandler\PruneUserNotificationsHandler;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PruneUserNotificationsHandlerTest extends TestCase
{
    public function testDeletesOldNotifications(): void
    {
        $message = new PruneUserNotifications();

        // We'll simulate 2 deleted rows returned by execute()
        $query = $this->createMock(\Doctrine\ORM\AbstractQuery::class);
        $query->expects($this->once())->method('execute')->willReturn(2);

        $qb = $this->getMockBuilder(\Doctrine\ORM\QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['delete', 'where', 'setParameter', 'getQuery'])
            ->getMock();

        $qb->expects($this->once())->method('delete')
            ->with(UserNotification::class, 'n')
            ->willReturnSelf();
        $qb->expects($this->once())->method('where')
            ->with($this->callback(fn ($expr) => str_contains($expr, 'n.creationTime')))
            ->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')
            ->with('before', $this->callback(fn ($dt) => $dt instanceof DateTimeImmutable))
            ->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')
            ->with('Pruned user notifications', ['deleted' => 2]);

        $handler = new PruneUserNotificationsHandler($em, $logger);
        $handler($message);
    }
}
