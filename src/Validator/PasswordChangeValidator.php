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
    private TokenStorageInterface $storage;
    private PasswordHasherFactoryInterface $passwordHasherFactory;

    /**
     * Constructor.
     */
    public function __construct(TokenStorageInterface $storage, PasswordHasherFactoryInterface $passwordHasherFactory)
    {
        $this->storage = $storage;
        $this->passwordHasherFactory = $passwordHasherFactory;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param PasswordChange $value
     *
     * @throws UnexpectedTypeException
     */
    public function validate($value, Constraint $constraint): bool
    {
        if (!$value instanceof PasswordChange) {
            throw new UnexpectedTypeException('Wrong value type given', Registration::class);
        }

        /** @var User $user */
        $user = $this->storage->getToken()->getUser();
        $hasher = $this->passwordHasherFactory->getPasswordHasher($user);

        if (!$hasher->verify($user->getPassword(), $value->password)) {
            $this->context->addViolation('form.wrong-password');

            return false;
        }

        if ($value->password === $value->getPlainPassword()) {
            $this->context->addViolation('form.identical-passwords');

            return false;
        }

        return true;
    }
}
