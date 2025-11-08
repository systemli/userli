<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Traits\EmailTrait;
use App\Traits\PlainPasswordTrait;
use App\Traits\RecoveryTokenTrait;

class RecoveryResetPassword
{
    use EmailTrait;
    use PlainPasswordTrait;
    use RecoveryTokenTrait;
}
