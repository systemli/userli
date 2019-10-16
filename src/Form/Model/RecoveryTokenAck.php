<?php

namespace App\Form\Model;

use App\Traits\RecoveryTokenTrait;

class RecoveryTokenAck
{
    use RecoveryTokenTrait;

    /**
     * @var bool
     */
    public $ack = false;
}
