<?php

namespace App\Validator;

use App\Entity\User;
use App\Form\Model\PasswordChange;
use App\Form\Model\Registration;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PasswordChangeValidator extends ConstraintValidator
{
    /**
     * @var TokenStorageInterface
     */
    private $storage;
    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * Constructor.
     */
    public function __construct(TokenStorageInterface $storage, EncoderFactoryInterface $encoderFactory)
    {
        $this->storage = $storage;
        $this->encoderFactory = $encoderFactory;
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
        $encoder = $this->encoderFactory->getEncoder($user);

        if (!$encoder->isPasswordValid($user->getPassword(), $value->password, $user->getSalt())) {
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
