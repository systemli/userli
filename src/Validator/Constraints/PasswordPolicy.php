<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PasswordPolicy extends Constraint
{
    public function validatedBy(): string
    {
        return 'password_policy';
    }
}
