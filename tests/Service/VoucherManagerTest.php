<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Domain;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Exception\ValidationException;
use App\Repository\VoucherRepository;
use App\Service\VoucherManager;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VoucherManagerTest extends TestCase
{
    private VoucherRepository&Stub $repository;
    private EntityManagerInterface&Stub $entityManager;
    private ValidatorInterface&Stub $validator;
    private VoucherManager $manager;
    private Domain $domain;

    protected function setUp(): void
    {
        $this->repository = $this->createStub(VoucherRepository::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->validator = $this->createStub(ValidatorInterface::class);
        $this->validator->method('validate')->willReturn(new ConstraintViolationList());

        $this->domain = new Domain();
        $this->domain->setName('example.org');

        $this->manager = new VoucherManager(
            $this->entityManager,
            $this->repository,
            $this->validator,
        );
    }

    public function testCreate(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Voucher::class));
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $manager = new VoucherManager($entityManager, $this->repository, $this->validator);

        $user = new User('test@example.org');
        $user->setDomain($this->domain);

        $voucher = $manager->create($user, $this->domain);

        self::assertInstanceOf(Voucher::class, $voucher);
        self::assertSame($user, $voucher->getUser());
        self::assertSame($this->domain, $voucher->getDomain());
        self::assertSame(6, strlen($voucher->getCode()));
    }

    public function testCreateWithValidationException(): void
    {
        $violation = new ConstraintViolation('message', 'messageTemplate', [], null, null, 'someValue');

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([$violation]));

        $manager = new VoucherManager($this->entityManager, $this->repository, $validator);

        $user = new User('test@example.org');
        $user->setDomain($this->domain);

        $this->expectException(ValidationException::class);

        $manager->create($user, $this->domain);
    }

    public function testAssertDomainPermissionAdmin(): void
    {
        $user = new User('admin@example.org');
        $user->setRoles([Roles::ADMIN]);
        $user->setDomain($this->domain);

        $otherDomain = new Domain();
        $otherDomain->setName('other.org');

        // Admin can create vouchers for any domain — should not throw
        $this->manager->assertDomainPermission($user, $otherDomain);

        $this->expectNotToPerformAssertions();
    }

    public function testAssertDomainPermissionOwnDomain(): void
    {
        $user = new User('user@example.org');
        $user->setRoles([Roles::USER]);
        $user->setDomain($this->domain);

        // User can create vouchers for own domain — should not throw
        $this->manager->assertDomainPermission($user, $this->domain);

        $this->expectNotToPerformAssertions();
    }

    public function testAssertDomainPermissionDifferentDomain(): void
    {
        $user = new User('user@example.org');
        $user->setRoles([Roles::USER]);
        $user->setDomain($this->domain);

        $otherDomain = new Domain();
        $otherDomain->setName('other.org');

        $this->expectException(InvalidArgumentException::class);

        $this->manager->assertDomainPermission($user, $otherDomain);
    }

    public function testGetVouchersByUserSuspicious(): void
    {
        $user = new User('suspicious@example.org');
        $user->setRoles([Roles::SUSPICIOUS]);

        $vouchers = $this->manager->getVouchersByUser($user);

        self::assertEmpty($vouchers);
    }

    public function testGetVouchersByUserNewUser(): void
    {
        $this->repository->method('findByUser')->willReturn([]);

        $user = new User('new@example.org');
        $user->setDomain($this->domain);
        $user->setCreationTime(new DateTimeImmutable());

        $vouchers = $this->manager->getVouchersByUser($user);

        self::assertEmpty($vouchers);
    }

    public function testGetVouchersByUserEnoughVouchers(): void
    {
        $this->repository->method('findByUser')->willReturn([
            new Voucher('code1'),
            new Voucher('code2'),
            new Voucher('code3'),
        ]);

        $user = new User('test@example.org');
        $user->setDomain($this->domain);
        $user->setCreationTime(new DateTimeImmutable('-8 days'));

        $vouchers = $this->manager->getVouchersByUser($user);

        self::assertNotEmpty($vouchers);
        self::assertCount(3, $vouchers);

        $vouchers = $this->manager->getVouchersByUser($user, true);

        self::assertNotEmpty($vouchers);
        self::assertCount(3, $vouchers);
    }

    public function testGetVouchersByUserNeedToCreateVouchers(): void
    {
        $this->repository->method('findByUser')->willReturn([
            new Voucher('code1'),
            new Voucher('code2'),
        ]);

        $user = new User('test@example.org');
        $user->setDomain($this->domain);
        $user->setCreationTime(new DateTimeImmutable('-8 days'));

        $vouchers = $this->manager->getVouchersByUser($user);

        self::assertNotEmpty($vouchers);
        self::assertCount(2, $vouchers);

        $user->setLastLoginTime(new DateTimeImmutable());
        $vouchers = $this->manager->getVouchersByUser($user);

        self::assertNotEmpty($vouchers);
        self::assertCount(3, $vouchers);
    }
}
