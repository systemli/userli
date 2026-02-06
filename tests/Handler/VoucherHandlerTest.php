<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Creator\VoucherCreator;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Handler\VoucherHandler;
use App\Repository\VoucherRepository;
use DateTimeImmutable;
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

        $user = new User('suspicious@example.org');
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

        $user = new User('new@example.org');
        $user->setCreationTime(new DateTimeImmutable());

        $vouchers = $handler->getVouchersByUser($user);

        self::assertEmpty($vouchers);
    }

    public function testFindEnoughVouchers(): void
    {
        $repository = $this->getMockBuilder(VoucherRepository::class)->disableOriginalConstructor()->getMock();
        $repository->method('findByUser')->willReturn([new Voucher('code1'), new Voucher('code2'), new Voucher('code3')]);

        $manager = $this->getMockBuilder(EntityManagerInterface::class)->disableOriginalConstructor()->getMock();
        $manager->method('getRepository')->willReturn($repository);

        $creator = $this->getMockBuilder(VoucherCreator::class)->disableOriginalConstructor()->getMock();

        $handler = new VoucherHandler($manager, $creator);

        $user = new User('test@example.org');
        $user->setCreationTime(new DateTimeImmutable('-8 days'));

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
        $repository->method('findByUser')->willReturn([new Voucher('code1'), new Voucher('code2')]);

        $manager = $this->getMockBuilder(EntityManagerInterface::class)->disableOriginalConstructor()->getMock();
        $manager->method('getRepository')->willReturn($repository);

        $creator = $this->getMockBuilder(VoucherCreator::class)->disableOriginalConstructor()->getMock();
        $creator->method('create')->willReturn(new Voucher('code3'));

        $handler = new VoucherHandler($manager, $creator);

        $user = new User('test@example.org');
        $user->setCreationTime(new DateTimeImmutable('-8 days'));

        $vouchers = $handler->getVouchersByUser($user);

        self::assertNotEmpty($vouchers);
        self::assertCount(2, $vouchers);

        $user->setLastLoginTime(new DateTimeImmutable());
        $vouchers = $handler->getVouchersByUser($user);

        self::assertNotEmpty($vouchers);
        self::assertCount(3, $vouchers);
    }
}
