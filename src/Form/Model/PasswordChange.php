<?php

namespace App\Form\Model;

use App\Traits\PlainPasswordTrait;

class PasswordChange
{
    use PlainPasswordTrait;

    /**
     * @var string
     */
    public $password;
}
