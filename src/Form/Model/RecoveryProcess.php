<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class RecoveryProcess
{
    /**
     * @var string
     */
    #[Assert\Email(mode: 'strict')]
    public $email;

    /**
     * @var string
     */
    #[Assert\Uuid(message: 'form.invalid-token')]
    public $recoveryToken;
}
