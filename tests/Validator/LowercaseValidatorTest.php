<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Validator\Lowercase;
use App\Validator\LowercaseValidator;
use stdClass;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class LowercaseValidatorTest extends ConstraintValidatorTestCase
{
    private $domain = 'example.org';
    private $minLength = 3;
    private $maxLength = 10;

    protected function createValidator(): LowercaseValidator
    {
        return new LowercaseValidator();
    }

    public function testExpectsStringType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('string', new Valid());
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new Lowercase());
        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new Lowercase());
        $this->assertNoViolation();
    }

    public function testExpectsStringCompatibleType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new stdClass(), new Lowercase());
    }

    public function testValidateValid(): void
    {
        $this->validator->validate('example#3!%', new Lowercase());
        $this->validator->validate('example.org', new Lowercase());
        $this->validator->validate('new@example.org', new Lowercase());
        $this->assertNoViolation();
    }

    /**
     * @dataProvider getStrings
     */
    public function testValidateUppercaseInvalid(string $string): void
    {
        $this->validator->validate($string, new Lowercase());
        $this->buildViolation('form.lowercase')
            ->setParameter('{{ string }}', $string)
            ->assertRaised();
    }

    public static function getStrings(): array
    {
        return [
            ['Example#3!%'],
            ['example.Org'],
            ['neW@example.org'],
        ];
    }
}
