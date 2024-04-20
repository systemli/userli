<?php

namespace App\Tests\Validator;

use App\Entity\User;
use App\Form\Model\PasswordChange;
use App\Validator\Constraints\PasswordChangeConstraint;
use App\Validator\PasswordChangeValidator;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PlaintextPasswordHasher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PasswordChangeValidatorTest extends ConstraintValidatorTestCase
{
    private readonly PlaintextPasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new PlaintextPasswordHasher();
        parent::setUp();
    }


    protected function createValidator(): PasswordChangeValidator
    {
        $user = new User();
        $user->setPassword($this->hasher->hash("password"));

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);

        $encoder = $this->createMock(PasswordHasherFactoryInterface::class);
        $encoder->method('getPasswordHasher')->willReturn($this->hasher);

        return new PasswordChangeValidator($storage, $encoder);
    }

    public function testRaiseException()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(null, new PasswordChangeConstraint());
    }

    public function testInvalidPassword()
    {
        $passwordChange = new PasswordChange();
        $passwordChange->password = $this->hasher->hash('invalid');

        $this->validator->validate($passwordChange, new PasswordChangeConstraint());

        $this->buildViolation('form.wrong-password')
            ->assertRaised();
    }

    public function testIdenticalPasswords()
    {
        $passwordChange = new PasswordChange();
        $passwordChange->password = $this->hasher->hash('password');
        $passwordChange->setPlainPassword('password');

        $this->validator->validate($passwordChange, new PasswordChangeConstraint());

        $this->buildViolation('form.identical-passwords')
            ->assertRaised();
    }
}
