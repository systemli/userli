<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PasswordPolicy extends Constraint
{
    public function validatedBy()
    {
        return 'password_policy';
    }
}
