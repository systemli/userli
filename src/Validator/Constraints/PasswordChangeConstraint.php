<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PasswordChangeConstraint extends Constraint
{
    public function validatedBy()
    {
        return 'password_change';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
