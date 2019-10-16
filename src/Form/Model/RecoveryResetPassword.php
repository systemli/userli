<?php

namespace App\Form\Model;

use App\Traits\EmailTrait;
use App\Traits\RecoveryTokenTrait;

class RecoveryResetPassword
{
    use EmailTrait;
    use RecoveryTokenTrait;

    /**
     * @var string
     */
    public $newPassword;
}
