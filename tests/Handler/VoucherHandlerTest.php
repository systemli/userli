<?php

namespace App\Tests\Handler;

use App\Creator\VoucherCreator;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Handler\VoucherHandler;
use App\Repository\VoucherRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

class VoucherHandlerTest extends TestCase
{
    public function testSuspiciousUser()
    {
        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $creator = $this->getMockBuilder(VoucherCreator::class)->disableOriginalConstructor()->getMock();

        $handler = new VoucherHandler($manager, $creator);

        $user = new User();
        $user->setRoles([Roles::SUSPICIOUS]);

        $vouchers = $handler->getVouchersByUser($user);

        self::assertEmpty($vouchers);
    }

    public function testNewUser()
    {
        $repository = $this->getMockBuilder(VoucherRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects($this->any())->method('findByUser')->willReturn([]);

        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        $creator = $this->getMockBuilder(VoucherCreator::class)->disableOriginalConstructor()->getMock();

        $handler = new VoucherHandler($manager, $creator);

        $user = new User();
        $user->setCreationTime(new \DateTime());

        $vouchers = $handler->getVouchersByUser($user);

        self::assertEmpty($vouchers);
    }

    public function testFindEnoughVouchers()
    {
        $repository = $this->getMockBuilder(VoucherRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects($this->any())->method('findByUser')->willReturn([new Voucher(), new Voucher(), new Voucher()]);

        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        $creator = $this->getMockBuilder(VoucherCreator::class)->disableOriginalConstructor()->getMock();

        $handler = new VoucherHandler($manager, $creator);

        $user = new User();
        $user->setCreationTime(new \DateTime('-8 days'));

        $vouchers = $handler->getVouchersByUser($user);

        self::assertNotEmpty($vouchers);
        self::assertCount(3, $vouchers);
    }

    public function testNeedToCreateVouchers()
    {
        $repository = $this->getMockBuilder(VoucherRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects($this->any())->method('findByUser')->willReturn([new Voucher(), new Voucher()]);

        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        $creator = $this->getMockBuilder(VoucherCreator::class)->disableOriginalConstructor()->getMock();
        $creator->expects($this->any())->method('create')->willReturn(new Voucher());

        $handler = new VoucherHandler($manager, $creator);

        $user = new User();
        $user->setCreationTime(new \DateTime('-8 days'));

        $vouchers = $handler->getVouchersByUser($user);

        self::assertNotEmpty($vouchers);
        self::assertCount(3, $vouchers);
    }
}
