<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\Voucher;
use App\Entity\User;
use App\Message\UnlinkRedeemedVouchers;
use App\MessageHandler\UnlinkRedeemedVouchersHandler;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\VoucherRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UnlinkRedeemedVouchersHandlerTest extends TestCase
{
    public function testUnlinksRedeemedVouchersOlderThanThreeMonths(): void
    {
        $voucher1 = new Voucher();
        $voucher1->setCode('A');
        $voucher1->setRedeemedTime((new DateTime('-4 months'))); // old
        $voucher1->setInvitedUser(new User());

        $voucher2 = new Voucher();
        $voucher2->setCode('B');
        $voucher2->setRedeemedTime(new DateTime('-5 months')); // old
        $voucher2->setInvitedUser(new User());

        $voucherRecent = new Voucher();
        $voucherRecent->setCode('C');
        $voucherRecent->setRedeemedTime(new DateTime('-1 month')); // recent (should not appear in result set)
        $voucherRecent->setInvitedUser(new User());

        $expectedResultSet = [$voucher1, $voucher2];

        $repo = $this->getMockBuilder(VoucherRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
        $repo->method('createQueryBuilder')->willReturnCallback(function() use (&$qb) {
            return $qb;
        });

        // We'll mock the QueryBuilder chain similarly as in other handler tests
        $qb = $this->getMockBuilder(\Doctrine\ORM\QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['join','where','setParameter','orderBy','getQuery'])
            ->getMock();

        // Because handler starts from repository->createQueryBuilder('voucher') we skip verifying alias there
        $qb->method('join')->willReturnSelf();
        $qb->method('where')->with($this->callback(fn($expr) => str_contains($expr, 'voucher.redeemedTime')))->willReturnSelf();
        $qb->method('setParameter')->with('date', $this->callback(fn($dt) => $dt instanceof DateTime))->willReturnSelf();
        $qb->method('orderBy')->with('voucher.redeemedTime')->willReturnSelf();

        $query = $this->getMockBuilder(\Doctrine\ORM\AbstractQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())->method('getResult')->willReturn($expectedResultSet);

        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);
        $em->expects($this->once())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with('Unlinked redeemed vouchers', ['count' => 2]);

        $handler = new UnlinkRedeemedVouchersHandler($em, $logger);
        $handler(new UnlinkRedeemedVouchers());

        $this->assertNull($voucher1->getInvitedUser());
        $this->assertNull($voucher2->getInvitedUser());
        $this->assertNotNull($voucherRecent->getInvitedUser(), 'Recent voucher should not be touched (not in result set)');
    }
}
