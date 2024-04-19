<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class RecoveryProcess
{
    #[Assert\Email(mode: 'strict')]
    public string $email;

    #[Assert\Uuid(message: 'form.invalid-token')]
    public string $recoveryToken;
}
