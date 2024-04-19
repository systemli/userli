<?php

namespace App\Form\Model;

use App\Traits\RecoveryTokenTrait;
use Symfony\Component\Validator\Constraints as Assert;

class RecoveryTokenAck
{
    use RecoveryTokenTrait;

    #[Assert\NotBlank(message: 'form.registration-recovery-token-noack')]
    public bool $ack = false;
}
