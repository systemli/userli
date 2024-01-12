<?php

namespace App\Tests\Handler;

use DateTime;
use App\Creator\VoucherCreator;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Handler\VoucherHandler;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class VoucherHandlerTest extends TestCase
{
    public function testSuspiciousUser(): void
    {
        $voucherRepository = $this->getMockBuilder(VoucherRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $manager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $manager->method('getRepository')
            ->willReturn($voucherRepository);
        $creator = $this->getMockBuilder(VoucherCreator::class)->disableOriginalConstructor()->getMock();

        $handler = new VoucherHandler($manager, $creator);

        $user = new User();
        $user->setRoles([Roles::SUSPICIOUS]);

        $vouchers = $handler->getVouchersByUser($user);

        self::assertEmpty($vouchers);
    }

    public function testNewUser(): void
    {
        $repository = $this->getMockBuilder(VoucherRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects($this->any())->method('findByUser')->willReturn([]);

        $manager = $this->getMockBuilder(EntityManagerInterface::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        $creator = $this->getMockBuilder(VoucherCreator::class)->disableOriginalConstructor()->getMock();

        $handler = new VoucherHandler($manager, $creator);

        $user = new User();
        $user->setCreationTime(new DateTime());

        $vouchers = $handler->getVouchersByUser($user);

        self::assertEmpty($vouchers);
    }

    public function testFindEnoughVouchers(): void
    {
        $repository = $this->getMockBuilder(VoucherRepository::class)->disableOriginalConstructor()->getMock();
        $repository->method('findByUser')->willReturn([new Voucher(), new Voucher(), new Voucher()]);

        $manager = $this->getMockBuilder(EntityManagerInterface::class)->disableOriginalConstructor()->getMock();
        $manager->method('getRepository')->willReturn($repository);

        $creator = $this->getMockBuilder(VoucherCreator::class)->disableOriginalConstructor()->getMock();

        $handler = new VoucherHandler($manager, $creator);

        $user = new User();
        $user->setCreationTime(new DateTime('-8 days'));

        $vouchers = $handler->getVouchersByUser($user);

        self::assertNotEmpty($vouchers);
        self::assertCount(3, $vouchers);

        $vouchers = $handler->getVouchersByUser($user, true);

        self::assertNotEmpty($vouchers);
        self::assertCount(3, $vouchers);
    }

    public function testNeedToCreateVouchers(): void
    {
        $repository = $this->getMockBuilder(VoucherRepository::class)->disableOriginalConstructor()->getMock();
        $repository->method('findByUser')->willReturn([new Voucher(), new Voucher()]);

        $manager = $this->getMockBuilder(EntityManagerInterface::class)->disableOriginalConstructor()->getMock();
        $manager->method('getRepository')->willReturn($repository);

        $creator = $this->getMockBuilder(VoucherCreator::class)->disableOriginalConstructor()->getMock();
        $creator->method('create')->willReturn(new Voucher());

        $handler = new VoucherHandler($manager, $creator);

        $user = new User();
        $user->setCreationTime(new DateTime('-8 days'));

        $vouchers = $handler->getVouchersByUser($user);

        self::assertNotEmpty($vouchers);
        self::assertCount(2, $vouchers);

        $user->setLastLoginTime(new DateTime());
        $vouchers = $handler->getVouchersByUser($user);

        self::assertNotEmpty($vouchers);
        self::assertCount(3, $vouchers);
    }
}
