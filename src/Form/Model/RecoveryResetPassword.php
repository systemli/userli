<?php

namespace App\Form\Model;

use App\Traits\EmailTrait;
use App\Traits\RecoveryTokenTrait;

/**
 * @author doobry <doobry@systemli.org>
 */
class RecoveryResetPassword
{
    use EmailTrait;
    use RecoveryTokenTrait;

    /**
     * @var string
     */
    public $newPassword;
}
