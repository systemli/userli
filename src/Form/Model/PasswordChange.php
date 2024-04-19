<?php

namespace App\Form\Model;

use App\Traits\PlainPasswordTrait;
use App\Validator\Constraints\PasswordChangeConstraint;

#[PasswordChangeConstraint]
class PasswordChange
{
    use PlainPasswordTrait;

    public string $password;
}
