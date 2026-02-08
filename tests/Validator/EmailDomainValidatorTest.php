<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Entity\User;
use App\Repository\DomainRepository;
use App\Validator\EmailDomain;
use App\Validator\EmailDomainValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EmailDomainValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): EmailDomainValidator
    {
        $repository = $this->createStub(DomainRepository::class);
        $repository->method('findOneBy')
            ->willReturn(null);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')
            ->willReturn($repository);

        return new EmailDomainValidator($manager);
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

    public function testDomainNotFound(): void
    {
        $user = new User('user@example.com');
        $this->validator->validate($user, new EmailDomain());

        $this->buildViolation('form.missing-domain')
            ->assertRaised();
    }
}
