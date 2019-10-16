<?php

namespace App\Validator;

use App\Entity\User;
use App\Form\Model\PasswordChange;
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
     *
     * @param TokenStorageInterface   $storage
     * @param EncoderFactoryInterface $encoderFactory
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
     * @param Constraint     $constraint
     *
     * @throws UnexpectedTypeException
     *
     * @return bool
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof PasswordChange) {
            throw new UnexpectedTypeException('Wrong value type given', 'App\Form\Model\Registration');
        }

        /** @var User $user */
        $user = $this->storage->getToken()->getUser();
        $encoder = $this->encoderFactory->getEncoder($user);

        if (!$encoder->isPasswordValid($user->getPassword(), $value->password, $user->getSalt())) {
            $this->context->addViolation('form.wrong-password');

            return false;
        }

        if ($value->password === $value->newPassword) {
            $this->context->addViolation('form.identical-passwords');

            return false;
        }

        return true;
    }
}
