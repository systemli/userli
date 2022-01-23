<?php

namespace App\Tests\Validator\Constraints;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\ReservedName;
use App\Entity\User;
use App\Repository\AliasRepository;
use App\Repository\DomainRepository;
use App\Repository\ReservedNameRepository;
use App\Repository\UserRepository;
use App\Validator\Constraints\EmailAddress;
use App\Validator\Constraints\EmailAddressValidator;
use Doctrine\Common\Persistence\ObjectManager;
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
            [explode('@', $this->addressNew)[1], new Domain()],
            [$this->extraDomain, new Domain()],
        ]);
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->method('findOneBy')->willReturnMap([
            [['email' => $this->userUsed], null, true, new User()],
        ]);
        $reservedNameRepository = $this->getMockBuilder(ReservedNameRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reservedNameRepository->method('findByName')->willReturnMap([
            ['reserved', new ReservedName()],
        ]);
        $manager = $this->getMockBuilder(ObjectManager::class)->getMock();
        $manager->method('getRepository')->willReturnMap([
            ['App:Alias', $aliasRepository],
            ['App:Domain', $domainRepository],
            ['App:ReservedName', $reservedNameRepository],
            ['App:User', $userRepository],
        ]);

        return new EmailAddressValidator($manager, $this->domain);
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
        $this->validator->validate(new \stdClass(), new EmailAddress());
    }

    /**
     * @dataProvider getValidNewAddresses
     */
    public function testValidateValidNewEmailAddress(string $address): void
    {
        $this->validator->validate($address, new EmailAddress());
        $this->assertNoViolation();
    }

    public function getValidNewAddresses(): array
    {
        return [
            [$this->addressNew],
        ];
    }

    /**
     * @dataProvider getInvalidNewAddresses
     */
    public function testValidateInvalidNewEmailAddress(string $address, string $violationMessage): void
    {
        $this->validator->validate($address, new EmailAddress());
        $this->buildViolation($violationMessage)
            ->assertRaised();
    }

    public function getInvalidNewAddresses(): array
    {
        return [
            ['!nvalid@'.$this->domain, 'registration.email-unexpected-characters'],
            ['new@nonexistant.org', 'registration.email-domain-not-exists'],
            //['new@'.$this->extraDomain, 'registration.email-domain-invalid'],
        ];
    }

    /**
     * @dataProvider getUsedAddresses
     */
    public function testValidateUsedEmailAddress(string $address, string $violationMessage): void
    {
        $this->validator->validate($address, new EmailAddress());
        $this->buildViolation($violationMessage)
            ->assertRaised();
    }

    public function getUsedAddresses(): array
    {
        return [
            [$this->userUsed, 'registration.email-already-taken'],
            [$this->aliasUsed, 'registration.email-already-taken'],
            ['reserved@'.$this->domain, 'registration.email-already-taken'],
        ];
    }
}
