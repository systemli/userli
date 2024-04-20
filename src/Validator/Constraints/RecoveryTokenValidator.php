<?php

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Dto\RecoveryDto;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use App\Handler\RecoveryTokenHandler;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class RecoveryTokenValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly RecoveryTokenHandler $recoveryTokenHandler,
    ) {
    }

    /**
     * Checks if recoveryToken matches User
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof RecoveryToken) {
            throw new UnexpectedTypeException($constraint, RecoveryToken::class);
        }
        if (!$value instanceof RecoveryDto) {
            throw new UnexpectedValueException($value, RecoveryDto::class);
        }

        if (!$value->email || !$value->lowerCaseRecoveryToken()) {
            throw new InvalidArgumentException('"email" and "recoveryToken" attributes cannot be null');
        }

        $user = $this->manager->getRepository(User::class)->findByEmail($value->email);
        if (!$user) {
            $this->context->buildViolation($constraint->message)->addViolation();
        } elseif (!$this->recoveryTokenHandler->verify($user, $value->lowerCaseRecoveryToken())) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
