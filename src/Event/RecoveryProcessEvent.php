<?php

namespace App\Event;

use App\Entity\User;
use App\Traits\UserAwareTrait;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class LoginEvent.
 */
class RecoveryProcessEvent extends Event
{
    use UserAwareTrait;

    const NAME = 'recovery_process_started';

    /**
     * Constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
