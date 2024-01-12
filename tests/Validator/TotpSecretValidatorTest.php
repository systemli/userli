<?php

namespace App\Tests\Validator;

use stdClass;
use App\Entity\User;
use App\Validator\Constraints\TotpSecret;
use App\Validator\TotpSecretValidator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class TotpSecretValidatorTest extends ConstraintValidatorTestCase
{
    private User $user;
    private TotpAuthenticatorInterface $totpAuthenticator;

    protected function createValidator(): TotpSecretValidator
    {
        $this->user = new User();
        $tokenInterface = $this->getMockBuilder(TokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenInterface->method('getUser')
            ->willReturn($this->user);
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenStorage->method('getToken')
            ->willReturn($tokenInterface);

        $this->totpAuthenticator = $this->getMockBuilder(TotpAuthenticatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new TotpSecretValidator($tokenStorage, $this->totpAuthenticator);
    }

    public function testExpectsTotpSecretType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('string', new Valid());
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new TotpSecret());
        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new TotpSecret());
        $this->assertNoViolation();
    }

    public function testExpectsStringCompatibleType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new stdClass(), new TotpSecret());
    }

    public function testValidateVoucherInvalid(): void
    {
        $totpSecret = 'invalid';
        $this->totpAuthenticator->expects(self::once())
            ->method('checkCode')
            ->with($this->user, $totpSecret)
            ->willReturn(false);

        $this->validator->validate($totpSecret, new TotpSecret());
        $this->buildViolation('form.twofactor-secret-invalid')
            ->assertRaised();
    }

    public function testValidateValid(): void
    {
        $totpSecret = 'valid';
        $this->totpAuthenticator->expects(self::once())
            ->method('checkCode')
            ->with($this->user, $totpSecret)
            ->willReturn(true);

        $this->validator->validate($totpSecret, new TotpSecret());
        $this->assertNoViolation();
    }
}
