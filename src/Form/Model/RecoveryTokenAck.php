<?php

namespace App\Form\Model;

use App\Traits\RecoveryTokenTrait;
use Symfony\Component\Validator\Constraints as Assert;

class RecoveryTokenAck
{
    use RecoveryTokenTrait;

    /**
     * @var bool
     */
    #[Assert\NotBlank(message: 'form.registration-recovery-token-noack')]
    public $ack = false;
}
