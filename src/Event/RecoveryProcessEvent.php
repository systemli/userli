<?php

namespace App\Event;

use App\Entity\User;
use App\Traits\LocaleTrait;
use App\Traits\UserAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class RecoveryProcessEvent extends Event
{
    use UserAwareTrait;
    use LocaleTrait;

    const NAME = 'recovery_process_started';

    public function __construct(
        private readonly User $user
    )
    {}
}
