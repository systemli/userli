<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PasswordChangeConstraint extends Constraint
{
    public function validatedBy(): string
    {
        return 'password_change';
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
