<?php

namespace App\Form\Model;

use App\Traits\RecoveryTokenTrait;

/**
 * @author doobry <doobry@systemli.org>
 */
class RecoveryTokenAck
{
    use RecoveryTokenTrait;

    /**
     * @var bool
     */
    public $ack = false;
}
