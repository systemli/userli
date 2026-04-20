<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\User;
use App\Entity\Voucher;
use App\Message\RemoveUnredeemedVouchers;
use App\MessageHandler\RemoveUnredeemedVouchersHandler;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RemoveUnredeemedVouchersHandlerTest extends TestCase
{
    public function testRemovesUnredeemedVouchersOfDeletedUsers(): void
    {
        $deletedUser = new User('deleted@example.org');
        $deletedUser->setDeleted(true);

        $voucher1 = new Voucher('A');
        $voucher1->setUser($deletedUser);

        $voucher2 = new Voucher('B');
        $voucher2->setUser($deletedUser);

        $expectedResultSet = [$voucher1, $voucher2];

        $qb = $this->getMockBuilder(\Doctrine\ORM\QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['join', 'where', 'andWhere', 'getQuery'])
            ->getMock();

        $qb->expects($this->any())->method('join')->willReturnSelf();
        $qb->expects($this->any())->method('where')->willReturnSelf();
        $qb->expects($this->any())->method('andWhere')->willReturnSelf();

        $query = $this->getMockBuilder(\Doctrine\ORM\Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResult'])
            ->getMock();
        $query->expects($this->once())->method('getResult')->willReturn($expectedResultSet);

        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $repo = $this->getMockBuilder(VoucherRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
        $repo->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())->method('getRepository')->willReturn($repo);
        $em->expects($this->exactly(2))->method('remove')->with($this->isInstanceOf(Voucher::class));
        $em->expects($this->once())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('Removed unredeemed vouchers of deleted users', ['count' => 2]);

        $handler = new RemoveUnredeemedVouchersHandler($em, $logger);
        $handler(new RemoveUnredeemedVouchers());
    }

    public function testHandlesEmptyResultGracefully(): void
    {
        $qb = $this->getMockBuilder(\Doctrine\ORM\QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['join', 'where', 'andWhere', 'getQuery'])
            ->getMock();

        $qb->expects($this->any())->method('join')->willReturnSelf();
        $qb->expects($this->any())->method('where')->willReturnSelf();
        $qb->expects($this->any())->method('andWhere')->willReturnSelf();

        $query = $this->getMockBuilder(\Doctrine\ORM\Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResult'])
            ->getMock();
        $query->expects($this->once())->method('getResult')->willReturn([]);

        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $repo = $this->getMockBuilder(VoucherRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
        $repo->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())->method('getRepository')->willReturn($repo);
        $em->expects($this->never())->method('remove');
        $em->expects($this->once())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('Removed unredeemed vouchers of deleted users', ['count' => 0]);

        $handler = new RemoveUnredeemedVouchersHandler($em, $logger);
        $handler(new RemoveUnredeemedVouchers());
    }
}
