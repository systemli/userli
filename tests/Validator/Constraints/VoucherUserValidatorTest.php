<?php

namespace App\Tests\Validator\Constraints;

use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Repository\VoucherRepository;
use App\Validator\Constraints\VoucherExists;
use App\Validator\Constraints\VoucherUser;
use App\Validator\Constraints\VoucherUserValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class VoucherUserValidatorTest extends ConstraintValidatorTestCase
{
    private Voucher $voucher;

    protected function createValidator(): VoucherUserValidator
    {
        $this->voucher = new Voucher();
        $this->voucher->setCode('code');

        return new VoucherUserValidator();
    }

    public function testExpectsVoucherUserType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('string', new Valid());
    }

    public function testNoSuspiciousUser(): void
    {
        $user = new User();
        $user->setRoles([Roles::USER, Roles::SUSPICIOUS]);
        $this->voucher->setUser($user);
        $this->validator->validate($this->voucher, new VoucherUser());
        $this->buildViolation('voucher.suspicious-user')
            ->assertRaised();
    }

    public function testValidVoucherUser(): void
    {
        $user = new User();
        $user->setRoles([Roles::USER]);
        $this->voucher->setUser($user);
        $this->validator->validate($this->voucher, new VoucherUser());
        $this->assertNoViolation();
    }
}
