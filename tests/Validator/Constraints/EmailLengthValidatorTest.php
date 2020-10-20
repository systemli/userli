<?php

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\EmailLength;
use App\Validator\Constraints\EmailLengthValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\MissingOptionsException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EmailLengthValidatorTest extends ConstraintValidatorTestCase
{
    private $domain = 'example.org';
    private $minLength = 3;
    private $maxLength = 10;
    private $emailLengthOptions = [
        'minLength' => 3,
        'maxLength' => 10,
    ];

    protected function createValidator(): EmailLengthValidator
    {
        $manager = $this->getMockBuilder(ObjectManager::class)->getMock();

        return new EmailLengthValidator($manager);
    }

    public function testExpectsEmailLengthType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('string', new Valid());
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new EmailLength($this->emailLengthOptions));

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new EmailLength($this->emailLengthOptions));

        $this->assertNoViolation();
    }

    public function testExpectsStringCompatibleType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), new EmailLength($this->emailLengthOptions));
    }

    public function testConstraintMissingOptions(): void
    {
        $this->expectException(MissingOptionsException::class);
        new EmailLength();
    }

    public function testConstraintGetDefaultOption(): void
    {
        $constraint = new EmailLength($this->emailLengthOptions);
        self::assertEquals($this->minLength, $constraint->minLength);
        self::assertEquals($this->maxLength, $constraint->maxLength);
    }

    public function testValidateValidNewEmailLength(): void
    {
        $this->validator->validate('new@example.org', new EmailLength($this->emailLengthOptions));
        $this->assertNoViolation();
    }

    /**
     * @dataProvider getShortLongAddresses
     */
    public function testValidateShortLongEmailLength(string $address, string $violationMessage, string $operator, int $limit): void
    {
        $this->validator->validate($address, new EmailLength($this->emailLengthOptions));
        $this->buildViolation($violationMessage)
            ->setParameter('%'.$operator.'%', $limit)
            ->assertRaised();
    }

    public function getShortLongAddresses(): array
    {
        return [
            ['s@'.$this->domain, 'registration.email-too-short', 'min', $this->minLength],
            ['thisaddressiswaytoolong@'.$this->domain, 'registration.email-too-long', 'max', $this->maxLength],
        ];
    }
}
