<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Validator\EmailAllowedSymbols;
use App\Validator\EmailAllowedSymbolsValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EmailAllowedSymbolsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): EmailAllowedSymbolsValidator
    {
        return new EmailAllowedSymbolsValidator();
    }

    public function testExpectsEmailAllowedSymbolsType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('string', new Valid());
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new EmailAllowedSymbols());

        self::assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new EmailAllowedSymbols());

        self::assertNoViolation();
    }

    public function testExpectsStringCompatibleType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new stdClass(), new EmailAllowedSymbols());
    }

    #[DataProvider('getValidAddresses')]
    public function testValidAddresses(string $address): void
    {
        $this->validator->validate($address, new EmailAllowedSymbols());
        self::assertNoViolation();
    }

    public static function getValidAddresses(): array
    {
        return [
            ['new@example.org'],
            ['user.name@example.org'],
            ['user-name@example.org'],
            ['user_name@example.org'],
            ['user123@example.org'],
        ];
    }

    #[DataProvider('getInvalidAddresses')]
    public function testInvalidAddresses(string $address): void
    {
        $this->validator->validate($address, new EmailAllowedSymbols());
        $this->buildViolation('registration.email-unexpected-characters')
            ->assertRaised();
    }

    public static function getInvalidAddresses(): array
    {
        return [
            ['!nvalid@example.org'],
            ['user+name@example.org'],
            ['user name@example.org'],
        ];
    }
}
