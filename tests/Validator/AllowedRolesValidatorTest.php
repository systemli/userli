<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Enum\Roles;
use App\Validator\AllowedRoles;
use App\Validator\AllowedRolesValidator;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AllowedRolesValidatorTest extends ConstraintValidatorTestCase
{
    private Security&Stub $security;

    protected function createValidator(): AllowedRolesValidator
    {
        $this->security = $this->createStub(Security::class);

        return new AllowedRolesValidator($this->security);
    }

    public function testExpectsAllowedRolesType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate([], new Valid());
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new AllowedRoles());

        self::assertNoViolation();
    }

    public function testEmptyArrayIsValid(): void
    {
        $this->validator->validate([], new AllowedRoles());

        self::assertNoViolation();
    }

    public function testAdminCanAssignAnyRole(): void
    {
        $this->security->method('isGranted')->willReturnMap([
            [Roles::ADMIN, null, true],
        ]);

        $this->validator->validate(
            [Roles::ADMIN, Roles::DOMAIN_ADMIN, Roles::USER, Roles::PERMANENT, Roles::SPAM, Roles::MULTIPLIER, Roles::SUSPICIOUS],
            new AllowedRoles(),
        );

        self::assertNoViolation();
    }

    public function testDomainAdminCanAssignReachableRoles(): void
    {
        $this->security->method('isGranted')->willReturnMap([
            [Roles::ADMIN, null, false],
        ]);

        $this->validator->validate(
            [Roles::USER, Roles::PERMANENT],
            new AllowedRoles(),
        );

        self::assertNoViolation();
    }

    public function testDomainAdminCannotAssignAdmin(): void
    {
        $this->security->method('isGranted')->willReturnMap([
            [Roles::ADMIN, null, false],
        ]);

        $this->validator->validate(
            [Roles::USER, Roles::ADMIN],
            new AllowedRoles(),
        );

        $this->buildViolation('form.role-not-allowed')
            ->setParameter('{{ role }}', Roles::ADMIN)
            ->assertRaised();
    }

    public function testDomainAdminCannotAssignDomainAdmin(): void
    {
        $this->security->method('isGranted')->willReturnMap([
            [Roles::ADMIN, null, false],
        ]);

        $this->validator->validate(
            [Roles::USER, Roles::DOMAIN_ADMIN],
            new AllowedRoles(),
        );

        $this->buildViolation('form.role-not-allowed')
            ->setParameter('{{ role }}', Roles::DOMAIN_ADMIN)
            ->assertRaised();
    }
}
