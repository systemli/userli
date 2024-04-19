<?php

namespace App\Form\Model;

use App\Traits\PlainPasswordTrait;
use App\Validator\Constraints\PasswordPolicy;
use App\Validator\Constraints\PasswordChangeConstraint;
use Symfony\Component\Validator\Constraints as Assert;

#[PasswordChangeConstraint]
class PasswordChange
{
    use PlainPasswordTrait;

    /**
     * @var string
     */
    public $password;
}
