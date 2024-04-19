<?php

namespace App\Tests\Validator\Constraints;

use App\Handler\PasswordStrengthHandler;
use App\Validator\Constraints\PasswordPolicy;
use App\Validator\PasswordPolicyValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PasswordPolicyValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): PasswordPolicyValidator
    {
        $passwordStrengthHandler = new PasswordStrengthHandler();
        return new PasswordPolicyValidator($passwordStrengthHandler);
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new PasswordPolicy());

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new PasswordPolicy());

        $this->assertNoViolation();
    }

    public function testValidPassword(): void
    {
        $this->validator->validate('Password123!', new PasswordPolicy());

        $this->assertNoViolation();
    }

    public function testInvalidPassword(): void
    {
        $this->validator->validate('password', new PasswordPolicy());

        $this->buildViolation('form.weak_password')
            ->assertRaised();
    }
}
