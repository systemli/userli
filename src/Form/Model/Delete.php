<?php

namespace App\Form\Model;

use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class Delete
{
    /**
     * @var string
     */
    #[UserPassword(message: 'form.wrong-password')]
    public $password;
}
