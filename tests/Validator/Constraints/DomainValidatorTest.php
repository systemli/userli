<?php

use App\Entity\Domain as DomainEntity;
use App\Repository\DomainRepository;
use App\Validator\Constraints\Domain;
use App\Validator\Constraints\DomainValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DomainValidatorTest extends ConstraintValidatorTestCase
{
    private $domain = 'example.org';

    protected function createValidator(): DomainValidator
    {
        $domainRepository = $this->getMockBuilder(DomainRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $domainRepository->method('findByName')->willReturnCallback(function ($name) {
            if ($name === $this->domain) {
                return new DomainEntity();
            }

            return null;
        });
        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $manager->method('getRepository')->willReturnMap([
            [DomainEntity::class, $domainRepository],
        ]);

        return new DomainValidator($manager);
    }

    public function testIsNotString()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(42, new Domain());
    }

    public function testIsValid()
    {
        $this->validator->validate('example.com', new Domain());

        $this->assertNoViolation();
    }

    public function testIsNotUnique()
    {
        $this->validator->validate($this->domain, new Domain());

        $this->buildViolation('form.already-exists')
            ->assertRaised();
    }
}
