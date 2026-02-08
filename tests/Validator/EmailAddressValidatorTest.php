<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\ReservedName;
use App\Entity\User;
use App\Repository\AliasRepository;
use App\Repository\DomainRepository;
use App\Repository\ReservedNameRepository;
use App\Repository\UserRepository;
use App\Validator\EmailAddress;
use App\Validator\EmailAddressValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EmailAddressValidatorTest extends ConstraintValidatorTestCase
{
    private $domain = 'example.org';
    private $addressNew = 'new@example.org';
    private $aliasUsed = 'alias@example.org';
    private $userUsed = 'user@example.org';
    private $extraDomain = 'extra.org';

    protected function createValidator(): EmailAddressValidator
    {
        $aliasRepository = $this->getMockBuilder(AliasRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $aliasRepository->method('findOneBySource')->willReturnMap([
            [$this->aliasUsed, true, new Alias()],
        ]);
        $domainRepository = $this->getMockBuilder(DomainRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $domainRepository->method('findByName')->willReturnMap([
            [explode('@', (string) $this->addressNew)[1], new Domain()],
            [$this->extraDomain, new Domain()],
        ]);
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->method('findOneBy')->willReturnMap([
            [['email' => $this->userUsed], null, new User($this->userUsed)],
        ]);
        $reservedNameRepository = $this->getMockBuilder(ReservedNameRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reservedNameRepository->method('findByName')->willReturnMap([
            ['reserved', new ReservedName()],
        ]);
        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $manager->method('getRepository')->willReturnMap([
            [Alias::class, $aliasRepository],
            [Domain::class, $domainRepository],
            [ReservedName::class, $reservedNameRepository],
            [User::class, $userRepository],
        ]);

        return new EmailAddressValidator($manager);
    }

    public function testExpectsEmailAddressType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('string', new Valid());
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new EmailAddress());

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new EmailAddress());

        $this->assertNoViolation();
    }

    public function testExpectsStringCompatibleType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new stdClass(), new EmailAddress());
    }

    #[DataProvider('getValidNewAddresses')]
    public function testValidateValidNewEmailAddress(string $address): void
    {
        $this->validator->validate($address, new EmailAddress());
        $this->assertNoViolation();
    }

    public static function getValidNewAddresses(): array
    {
        return [
            ['new@example.org'],
        ];
    }

    #[DataProvider('getInvalidNewAddresses')]
    public function testValidateInvalidNewEmailAddress(string $address, string $violationMessage): void
    {
        $this->validator->validate($address, new EmailAddress());
        $this->buildViolation($violationMessage)
            ->assertRaised();
    }

    public static function getInvalidNewAddresses(): array
    {
        return [
            ['!nvalid@example.org', 'registration.email-unexpected-characters'],
            ['new@nonexistant.org', 'registration.email-domain-not-exists'],
            // ['new@extra.org', 'registration.email-domain-invalid'],
        ];
    }

    #[DataProvider('getUsedAddresses')]
    public function testValidateUsedEmailAddress(string $address, string $violationMessage): void
    {
        $this->validator->validate($address, new EmailAddress());
        $this->buildViolation($violationMessage)
            ->assertRaised();
    }

    public static function getUsedAddresses(): array
    {
        return [
            ['user@example.org', 'registration.email-already-taken'],
            ['alias@example.org', 'registration.email-already-taken'],
            ['reserved@example.org', 'registration.email-already-taken'],
        ];
    }
}
