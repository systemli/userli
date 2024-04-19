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

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (empty($value) || !is_string($value)) {
            return;
        }

        $errors = $this->handler->validate($value);

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->context->addViolation($error);
            }
        }
    }
}
