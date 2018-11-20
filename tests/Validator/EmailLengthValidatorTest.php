<?php

namespace App\Tests\Validator;

use App\Validator\Constraints\EmailLength;
use App\Validator\EmailLengthValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraints\Valid;
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

    protected function createValidator()
    {
        $manager = $this->getMockBuilder(ObjectManager::class)->getMock();

        return new EmailLengthValidator($manager);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testExpectsEmailLengthType()
    {
        $this->validator->validate('string', new Valid());
    }

    public function testNullIsValid()
    {
        $this->validator->validate(null, new EmailLength($this->emailLengthOptions));

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid()
    {
        $this->validator->validate('', new EmailLength($this->emailLengthOptions));

        $this->assertNoViolation();
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testExpectsStringCompatibleType()
    {
        $this->validator->validate(new \stdClass(), new EmailLength($this->emailLengthOptions));
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\MissingOptionsException
     */
    public function testConstraintMissingOptions()
    {
        new EmailLength();
    }

    public function testConstraintGetDefaultOption()
    {
        $constraint = new EmailLength($this->emailLengthOptions);
        $this->assertEquals($this->minLength, $constraint->minLength);
        $this->assertEquals($this->maxLength, $constraint->maxLength);
    }

    public function testValidateValidNewEmailLength()
    {
        $this->validator->validate('new@example.org', new EmailLength($this->emailLengthOptions));
        $this->assertNoViolation();
    }

    /**
     * @param string $address
     * @param string $violationMessage
     * @param string $operator
     * @param int    $limit
     * @dataProvider getShortLongAddresses
     */
    public function testValidateShortLongEmailLength(string $address, string $violationMessage, string $operator, int $limit)
    {
        $this->validator->validate($address, new EmailLength($this->emailLengthOptions));
        $this->buildViolation($violationMessage)
            ->setParameter('%'.$operator.'%', $limit)
            ->assertRaised();
    }

    public function getShortLongAddresses()
    {
        return [
            ['s@'.$this->domain, 'registration.email-too-short', 'min', $this->minLength],
            ['thisaddressiswaytoolong@'.$this->domain, 'registration.email-too-long', 'max', $this->maxLength],
        ];
    }
}
