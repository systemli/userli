<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\WebhookDelivery;
use App\Message\PruneWebhookDeliveries;
use App\MessageHandler\PruneWebhookDeliveriesHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PruneWebhookDeliveriesHandlerTest extends TestCase
{
    public function testDeletesOldDeliveries(): void
    {
        $message = new PruneWebhookDeliveries();

        $query = $this->createMock(\Doctrine\ORM\AbstractQuery::class);
        $query->expects($this->once())
            ->method('execute')
            ->willReturn(5);

        $qb = $this->getMockBuilder(\Doctrine\ORM\QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['delete', 'where', 'andWhere', 'setParameter', 'getQuery'])
            ->getMock();

        $qb->expects($this->once())
            ->method('delete')
            ->with(WebhookDelivery::class, 'd')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with($this->callback(static fn ($expr) => str_contains($expr, 'd.dispatchedTime')))
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('d.success = :success')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Pruned webhook deliveries', ['deleted' => 5]);

        $handler = new PruneWebhookDeliveriesHandler($em, $logger);
        $handler($message);
    }
}
