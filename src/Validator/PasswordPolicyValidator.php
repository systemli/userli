<?php

namespace App\Validator;

use App\Handler\PasswordStrengthHandler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PasswordPolicyValidator extends ConstraintValidator
{
    public function __construct(private readonly PasswordStrengthHandler $handler)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): bool
    {
        if (empty($value)) {
            return true;
        }

        $errors = $this->handler->validate($value);

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->context->addViolation($error);
            }

            return false;
        }

        return true;
    }
}
