<?php

namespace App\Event;

use App\Entity\User;
use App\Traits\UserAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class UserEvent extends Event
{
    use UserAwareTrait;

    /**
     * Constructor.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
