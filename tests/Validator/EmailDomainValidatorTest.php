<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Entity\Domain;
use App\Repository\DomainRepository;
use App\Validator\EmailDomain;
use App\Validator\EmailDomainValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EmailDomainValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): EmailDomainValidator
    {
        $domainRepository = $this->createStub(DomainRepository::class);
        $domainRepository->method('findByName')->willReturnMap([
            ['example.org', new Domain()],
        ]);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnMap([
            [Domain::class, $domainRepository],
        ]);

        return new EmailDomainValidator($manager);
    }

    public function testExpectsEmailDomainType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('string', new Valid());
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new EmailDomain());

        self::assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new EmailDomain());

        self::assertNoViolation();
    }

    public function testExpectsStringCompatibleType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new stdClass(), new EmailDomain());
    }

    public function testValidDomain(): void
    {
        $this->validator->validate('new@example.org', new EmailDomain());
        self::assertNoViolation();
    }

    #[DataProvider('getInvalidDomains')]
    public function testInvalidDomain(string $address): void
    {
        $this->validator->validate($address, new EmailDomain());
        $this->buildViolation('registration.email-domain-not-exists')
            ->assertRaised();
    }

    public static function getInvalidDomains(): array
    {
        return [
            ['new@nonexistant.org'],
            ['user@unknown.com'],
        ];
    }
}
