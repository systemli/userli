<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Entity\Domain;
use App\Validator\UniqueField;
use App\Validator\UniqueFieldValidator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use stdClass;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueFieldValidatorTest extends ConstraintValidatorTestCase
{
    private EntityManagerInterface $manager;
    private EntityRepository $repository;

    protected function createValidator(): UniqueFieldValidator
    {
        $this->repository = $this->createStub(EntityRepository::class);

        $this->manager = $this->createStub(EntityManagerInterface::class);
        $this->manager->method('getRepository')
            ->willReturn($this->repository);

        return new UniqueFieldValidator($this->manager);
    }

    public function testExpectsUniqueFieldConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('value', new Valid());
    }

    public function testNullIsValid(): void
    {
        $constraint = new UniqueField(entityClass: Domain::class, field: 'name');
        $this->validator->validate(null, $constraint);

        self::assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $constraint = new UniqueField(entityClass: Domain::class, field: 'name');
        $this->validator->validate('', $constraint);

        self::assertNoViolation();
    }

    public function testExpectsStringValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $constraint = new UniqueField(entityClass: Domain::class, field: 'name');
        $this->validator->validate(new stdClass(), $constraint);
    }

    public function testUniqueValueIsValid(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => 'unique-domain'])
            ->willReturn(null);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $this->validator = new UniqueFieldValidator($manager);
        $this->validator->initialize($this->context);

        $constraint = new UniqueField(entityClass: Domain::class, field: 'name');
        $this->validator->validate('unique-domain', $constraint);

        self::assertNoViolation();
    }

    public function testDuplicateValueRaisesViolation(): void
    {
        $existingDomain = new Domain();

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => 'existing-domain'])
            ->willReturn($existingDomain);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $this->validator = new UniqueFieldValidator($manager);
        $this->validator->initialize($this->context);

        $constraint = new UniqueField(entityClass: Domain::class, field: 'name');
        $this->validator->validate('existing-domain', $constraint);

        $this->buildViolation('form.unique-field')
            ->setParameter('{{ value }}', 'existing-domain')
            ->setParameter('{{ field }}', 'name')
            ->assertRaised();
    }

    public function testCustomMessageIsUsed(): void
    {
        $existingDomain = new Domain();

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => 'duplicate'])
            ->willReturn($existingDomain);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $this->validator = new UniqueFieldValidator($manager);
        $this->validator->initialize($this->context);

        $constraint = new UniqueField(
            entityClass: Domain::class,
            field: 'name',
            message: 'custom.error-message'
        );
        $this->validator->validate('duplicate', $constraint);

        $this->buildViolation('custom.error-message')
            ->setParameter('{{ value }}', 'duplicate')
            ->setParameter('{{ field }}', 'name')
            ->assertRaised();
    }
}
