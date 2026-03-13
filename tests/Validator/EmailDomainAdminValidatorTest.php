<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Entity\Domain;
use App\Entity\User;
use App\Service\DomainGuesser;
use App\Validator\EmailDomainAdmin;
use App\Validator\EmailDomainAdminValidator;
use PHPUnit\Framework\MockObject\Stub;
use stdClass;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EmailDomainAdminValidatorTest extends ConstraintValidatorTestCase
{
    private Security&Stub $security;
    private DomainGuesser&Stub $domainGuesser;
    private Domain $domainA;
    private Domain $domainB;

    protected function createValidator(): EmailDomainAdminValidator
    {
        $this->domainA = new Domain();
        $this->domainB = new Domain();

        $this->security = $this->createStub(Security::class);
        $this->domainGuesser = $this->createStub(DomainGuesser::class);

        return new EmailDomainAdminValidator($this->security, $this->domainGuesser);
    }

    public function testExpectsEmailDomainAdminType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('string', new Valid());
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new EmailDomainAdmin());

        self::assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new EmailDomainAdmin());

        self::assertNoViolation();
    }

    public function testExpectsStringCompatibleType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new stdClass(), new EmailDomainAdmin());
    }

    public function testFullAdminCanCreateInAnyDomain(): void
    {
        $this->security->method('isGranted')->willReturn(true);

        $this->validator->validate('user@other-domain.org', new EmailDomainAdmin());

        self::assertNoViolation();
    }

    public function testDomainAdminCanCreateInOwnDomain(): void
    {
        $domainAdmin = new User('admin@domain-a.org');
        $domainAdmin->setDomain($this->domainA);

        $this->security->method('isGranted')->willReturn(false);
        $this->security->method('getUser')->willReturn($domainAdmin);
        $this->domainGuesser->method('guess')->willReturn($this->domainA);

        $this->validator->validate('newuser@domain-a.org', new EmailDomainAdmin());

        self::assertNoViolation();
    }

    public function testDomainAdminCannotCreateInDifferentDomain(): void
    {
        $domainAdmin = new User('admin@domain-a.org');
        $domainAdmin->setDomain($this->domainA);

        $this->security->method('isGranted')->willReturn(false);
        $this->security->method('getUser')->willReturn($domainAdmin);
        $this->domainGuesser->method('guess')->willReturn($this->domainB);

        $this->validator->validate('newuser@domain-b.org', new EmailDomainAdmin());

        $this->buildViolation('form.email-domain-not-allowed')
            ->assertRaised();
    }

    public function testDomainAdminCannotCreateInUnknownDomain(): void
    {
        $domainAdmin = new User('admin@domain-a.org');
        $domainAdmin->setDomain($this->domainA);

        $this->security->method('isGranted')->willReturn(false);
        $this->security->method('getUser')->willReturn($domainAdmin);
        $this->domainGuesser->method('guess')->willReturn(null);

        $this->validator->validate('newuser@nonexistent.org', new EmailDomainAdmin());

        $this->buildViolation('form.email-domain-not-found')
            ->assertRaised();
    }
}
