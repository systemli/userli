<?php

namespace App\Validator;

use App\Entity\User;
use App\Form\Model\PasswordChange;
use App\Form\Model\Registration;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PasswordChangeValidator extends ConstraintValidator
{
    public function __construct(private readonly TokenStorageInterface $storage, private readonly PasswordHasherFactoryInterface $passwordHasherFactory)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof PasswordChange) {
            throw new UnexpectedTypeException('Wrong value type given', Registration::class);
        }

        /** @var User $user */
        $user = $this->storage->getToken()->getUser();
        $hasher = $this->passwordHasherFactory->getPasswordHasher($user);

        if (!$hasher->verify($user->getPassword(), $value->password)) {
            $this->context->addViolation('form.wrong-password');
        }

        if ($value->password === $value->getPlainPassword()) {
            $this->context->addViolation('form.identical-passwords');
        }
    }
}
