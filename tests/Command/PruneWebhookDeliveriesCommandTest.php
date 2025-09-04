<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\PruneWebhookDeliveriesCommand;
use App\Entity\WebhookDelivery;
use DateTimeImmutable;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PruneWebhookDeliveriesCommandTest extends TestCase
{
    public function testExecuteDeletesOlderThan14Days(): void
    {
        $expectedDeleted = 42;

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())->method('execute')->willReturn($expectedDeleted);

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['delete', 'where', 'setParameter', 'getQuery'])
            ->getMock();

        $qb->expects($this->once())
            ->method('delete')
            ->with(WebhookDelivery::class, 'd')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with($this->callback(function (string $expr): bool {
                $this->assertSame('d.dispatchedTime < :before', $expr);
                return true;
            }))
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with($this->equalTo('before'), $this->callback(function ($value) {
                $this->assertInstanceOf(DateTimeImmutable::class, $value);
                // value should be roughly now - 14 days (allow small delta)
                $diff = (new DateTimeImmutable())->getTimestamp() - $value->getTimestamp();
                $this->assertTrue($diff >= 14 * 24 * 3600 - 10 && $diff <= 14 * 24 * 3600 + 10);
                return true;
            }))
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $command = new PruneWebhookDeliveriesCommand($em);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Deleted: ' . $expectedDeleted, $output);
        $this->assertSame(0, $tester->getStatusCode());
    }
}
