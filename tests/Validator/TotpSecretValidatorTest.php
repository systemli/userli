<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Entity\User;
use App\Validator\TotpSecret;
use App\Validator\TotpSecretValidator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use stdClass;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class TotpSecretValidatorTest extends ConstraintValidatorTestCase
{
    private User $user;
    private TotpAuthenticatorInterface $totpAuthenticator;
    private TokenStorageInterface $tokenStorage;

    protected function createValidator(): TotpSecretValidator
    {
        $this->user = new User('test@example.org');
        $tokenInterface = $this->createStub(TokenInterface::class);
        $tokenInterface->method('getUser')
            ->willReturn($this->user);
        $this->tokenStorage = $this->createStub(TokenStorageInterface::class);
        $this->tokenStorage->method('getToken')
            ->willReturn($tokenInterface);

        $this->totpAuthenticator = $this->createStub(TotpAuthenticatorInterface::class);

        return new TotpSecretValidator($this->tokenStorage, $this->totpAuthenticator);
    }

    public function testExpectsTotpSecretType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('string', new Valid());
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new TotpSecret());
        self::assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new TotpSecret());
        self::assertNoViolation();
    }

    public function testExpectsStringCompatibleType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new stdClass(), new TotpSecret());
    }

    public function testValidateVoucherInvalid(): void
    {
        $totpSecret = 'invalid';
        $totpAuthenticator = $this->createMock(TotpAuthenticatorInterface::class);
        $totpAuthenticator->expects(self::once())
            ->method('checkCode')
            ->with($this->user, $totpSecret)
            ->willReturn(false);

        $this->validator = new TotpSecretValidator($this->tokenStorage, $totpAuthenticator);
        $this->validator->initialize($this->context);

        $this->validator->validate($totpSecret, new TotpSecret());
        $this->buildViolation('form.twofactor-secret-invalid')
            ->assertRaised();
    }

    public function testValidateValid(): void
    {
        $totpSecret = 'valid';
        $totpAuthenticator = $this->createMock(TotpAuthenticatorInterface::class);
        $totpAuthenticator->expects(self::once())
            ->method('checkCode')
            ->with($this->user, $totpSecret)
            ->willReturn(true);

        $this->validator = new TotpSecretValidator($this->tokenStorage, $totpAuthenticator);
        $this->validator->initialize($this->context);

        $this->validator->validate($totpSecret, new TotpSecret());
        self::assertNoViolation();
    }
}
