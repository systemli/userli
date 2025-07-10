<?php

namespace App\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class PasswordPolicy extends Constraint
{
    public function validatedBy(): string
    {
        return 'password_policy';
    }
}
