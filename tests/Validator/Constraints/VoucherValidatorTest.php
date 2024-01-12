<?php

namespace App\Tests\Validator\Constraints;

use stdClass;
use App\Entity\Voucher;
use App\Repository\VoucherRepository;
use App\Validator\Constraints\Voucher as VoucherConstraint;
use App\Validator\Constraints\VoucherValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\MissingOptionsException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class VoucherValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): VoucherValidator
    {
        $voucher = new Voucher();
        $voucher->setCode('code');
        $repository = $this->getMockBuilder(VoucherRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->method('findByCode')->willReturnMap([
            ['code', $voucher],
        ]);
        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $manager->method('getRepository')->willReturn($repository);

        return new VoucherValidator($manager);
    }

    public function testExpectsVoucherType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('string', new Valid());
    }

    public function testNullIsInvalid(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(null, new VoucherConstraint(true));
    }

    public function testEmptyStringIsInvalid(): void
    {
        $this->validator->validate('', new VoucherConstraint(true));

        $this->buildViolation('registration.voucher-invalid')
            ->assertRaised();
    }

    public function testExpectsStringCompatibleType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new stdClass(), new VoucherConstraint(true));
    }

    public function testConstraintMissingOptions(): void
    {
        $this->expectException(MissingOptionsException::class);
        new VoucherConstraint();
    }

    public function testConstraintGetDefaultOption(): void
    {
        $constraint = new VoucherConstraint(true);
        self::assertEquals(true, $constraint->exists);
    }

    public function testValidateVoucherInvalid(): void
    {
        $this->validator->validate('code2', new VoucherConstraint(true));
        $this->buildViolation('registration.voucher-invalid')
            ->assertRaised();
    }

    public function testValidateVoucherUnused(): void
    {
        $this->validator->validate('code', new VoucherConstraint(true));
        $this->assertNoViolation();
    }

    public function testValidateVoucherNew(): void
    {
        $this->validator->validate('new', new VoucherConstraint(false));
        $this->assertNoViolation();
    }

    public function testValidateVoucherNewExists(): void
    {
        $this->validator->validate('code', new VoucherConstraint(false));
        $this->buildViolation('registration.voucher-exists')
            ->assertRaised();
    }
}
