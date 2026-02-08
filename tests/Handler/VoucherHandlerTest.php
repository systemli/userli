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
        $voucherRepository = $this->createStub(VoucherRepository::class);
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')
            ->willReturn($voucherRepository);
        $creator = $this->createStub(VoucherCreator::class);

        $handler = new VoucherHandler($manager, $creator);

        $user = new User('suspicious@example.org');
        $user->setRoles([Roles::SUSPICIOUS]);

        $vouchers = $handler->getVouchersByUser($user);

        self::assertEmpty($vouchers);
    }

    public function testNewUser(): void
    {
        $repository = $this->createStub(VoucherRepository::class);
        $repository->method('findByUser')->willReturn([]);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $creator = $this->createStub(VoucherCreator::class);

        $handler = new VoucherHandler($manager, $creator);

        $user = new User('new@example.org');
        $user->setCreationTime(new DateTimeImmutable());

        $vouchers = $handler->getVouchersByUser($user);

        self::assertEmpty($vouchers);
    }

    public function testFindEnoughVouchers(): void
    {
        $repository = $this->createStub(VoucherRepository::class);
        $repository->method('findByUser')->willReturn([new Voucher('code1'), new Voucher('code2'), new Voucher('code3')]);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $creator = $this->createStub(VoucherCreator::class);

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
        $repository = $this->createStub(VoucherRepository::class);
        $repository->method('findByUser')->willReturn([new Voucher('code1'), new Voucher('code2')]);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $creator = $this->createStub(VoucherCreator::class);
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
