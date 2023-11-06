<?php

namespace App\Event;

use App\Entity\User;
use App\Traits\UserAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class RecoveryProcessEvent.
 */
class RecoveryProcessEvent extends Event
{
    use UserAwareTrait;

    public const NAME = 'recovery_process_started';

    /**
     * Constructor.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
