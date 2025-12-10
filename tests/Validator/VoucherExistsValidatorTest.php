<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Repository\VoucherRepository;
use App\Validator\VoucherExists;
use App\Validator\VoucherExistsValidator;
use Doctrine\ORM\EntityManagerInterface;
use stdClass;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\MissingOptionsException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class VoucherExistsValidatorTest extends ConstraintValidatorTestCase
{
    private User $user;
    private Voucher $voucher;

    protected function createValidator(): VoucherExistsValidator
    {
        $this->user = new User('test@example.org');
        $this->voucher = new Voucher();
        $this->voucher->setCode('code');
        $this->voucher->setUser($this->user);
        $repository = $this->getMockBuilder(VoucherRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->method('findByCode')->willReturnMap([
            ['code', $this->voucher],
        ]);
        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $manager->method('getRepository')->willReturn($repository);

        return new VoucherExistsValidator($manager);
    }

    public function testExpectsVoucherType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('string', new Valid());
    }

    public function testNullIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(null, new VoucherExists(true));
    }

    public function testEmptyStringIsInvalid(): void
    {
        $this->validator->validate('', new VoucherExists(true));

        $this->buildViolation('registration.voucher-invalid')
            ->assertRaised();
    }

    public function testExpectsStringCompatibleType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new stdClass(), new VoucherExists(true));
    }

    public function testConstraintMissingOptions(): void
    {
        $this->expectException(MissingOptionsException::class);
        new VoucherExists();
    }

    public function testConstraintGetDefaultOption(): void
    {
        $constraint = new VoucherExists(true);
        self::assertTrue($constraint->exists);
    }

    public function testValidateVoucherInvalid(): void
    {
        $this->validator->validate('code2', new VoucherExists(true));
        $this->buildViolation('registration.voucher-invalid')
            ->assertRaised();
    }

    public function testValidateSuspiciousVoucherInvalid(): void
    {
        $this->user->setRoles([Roles::SUSPICIOUS]);
        $this->validator->validate('code', new VoucherExists(true));
        $this->buildViolation('registration.voucher-invalid')
            ->assertRaised();
    }

    public function testValidateVoucherUnused(): void
    {
        $this->validator->validate('code', new VoucherExists(true));
        $this->assertNoViolation();
    }

    public function testValidateVoucherNew(): void
    {
        $this->validator->validate('new', new VoucherExists(false));
        $this->assertNoViolation();
    }

    public function testValidateVoucherNewExists(): void
    {
        $this->validator->validate('code', new VoucherExists(false));
        $this->buildViolation('registration.voucher-exists')
            ->assertRaised();
    }
}
