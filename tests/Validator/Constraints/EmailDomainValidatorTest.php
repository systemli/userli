<?php

namespace App\Tests\Validator\Constraints;

use App\Entity\User;
use App\Repository\DomainRepository;
use App\Validator\Constraints\EmailDomain;
use App\Validator\Constraints\EmailDomainValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EmailDomainValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): EmailDomainValidator
    {
        $repository = $this->createMock(DomainRepository::class);
        $repository->expects($this->any())
            ->method('findOneBy')
            ->will($this->returnValue(null));

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        return new EmailDomainValidator($manager);
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new EmailDomain());

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new EmailDomain());

        $this->assertNoViolation();
    }

    public function testDomainNotFound(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $this->validator->validate($user, new EmailDomain());

        $this->buildViolation('form.missing-domain')
            ->assertRaised();
    }
}
