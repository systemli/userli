<?php

namespace App\Tests\Validator;

use App\Repository\AliasRepository;
use App\Repository\DomainRepository;
use App\Repository\ReservedNameRepository;
use App\Repository\UserRepository;
use App\Validator\Constraints\EmailAddress;
use App\Validator\EmailAddressValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EmailAddressValidatorTest extends ConstraintValidatorTestCase
{
    private $domain = 'example.org';
    private $addressNew = 'new@example.org';
    private $aliasUsed = 'alias@example.org';
    private $userUsed = 'user@example.org';
    private $extraDomain = 'extra.org';

    protected function createValidator()
    {
        $aliasRepository = $this->getMockBuilder(AliasRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $aliasRepository->expects($this->any())->method('findOneBySource')->willReturnMap([
            [$this->aliasUsed, true, true],
        ]);
        $domainRepository = $this->getMockBuilder(DomainRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $domainRepository->expects($this->any())->method('findByName')->willReturnMap([
            [explode('@', $this->addressNew)[1], true],
            [$this->extraDomain, true],
        ]);
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->expects($this->any())->method('findByEmail')->willReturnMap([
            [$this->userUsed, true],
        ]);
        $reservedNameRepository = $this->getMockBuilder(ReservedNameRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reservedNameRepository->expects($this->any())->method('findByName')->willReturnMap([
            ['reserved', true],
        ]);
        $manager = $this->getMockBuilder(ObjectManager::class)->getMock();
        $manager->expects($this->any())->method('getRepository')->willReturnMap([
            ['App:Alias', $aliasRepository],
            ['App:Domain', $domainRepository],
            ['App:ReservedName', $reservedNameRepository],
            ['App:User', $userRepository],
        ]);

        return new EmailAddressValidator($manager, $this->domain);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testExpectsEmailAddressType()
    {
        $this->validator->validate('string', new Valid());
    }

    public function testNullIsValid()
    {
        $this->validator->validate(null, new EmailAddress());

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid()
    {
        $this->validator->validate('', new EmailAddress());

        $this->assertNoViolation();
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testExpectsStringCompatibleType()
    {
        $this->validator->validate(new \stdClass(), new EmailAddress());
    }

    /**
     * @param string $address
     * @dataProvider getValidNewAddresses
     */
    public function testValidateValidNewEmailAddress(string $address)
    {
        $this->validator->validate($address, new EmailAddress());
        $this->assertNoViolation();
    }

    public function getValidNewAddresses()
    {
        return [
            [$this->addressNew],
        ];
    }

    /**
     * @param string $address
     * @param string $violationMessage
     * @dataProvider getInvalidNewAddresses
     */
    public function testValidateInvalidNewEmailAddress(string $address, string $violationMessage)
    {
        $this->validator->validate($address, new EmailAddress());
        $this->buildViolation($violationMessage)
            ->assertRaised();
    }

    public function getInvalidNewAddresses()
    {
        return [
            ['!nvalid@'.$this->domain, 'registration.email-unexpected-characters'],
            ['new@nonexistant.org', 'registration.email-domain-not-exists'],
            //['new@'.$this->extraDomain, 'registration.email-domain-invalid'],
        ];
    }

    /**
     * @param string $address
     * @param string $violationMessage
     * @dataProvider getUsedAddresses
     */
    public function testValidateUsedEmailAddress(string $address, string $violationMessage)
    {
        $this->validator->validate($address, new EmailAddress());
        $this->buildViolation($violationMessage)
            ->assertRaised();
    }

    public function getUsedAddresses()
    {
        return [
            [$this->userUsed, 'registration.email-already-taken'],
            [$this->aliasUsed, 'registration.email-already-taken'],
            ['reserved@'.$this->domain, 'registration.email-already-taken'],
        ];
    }
}
