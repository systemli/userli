<?php

namespace App\Event;

use App\Entity\User;
use App\Traits\UserAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class UserDeletedEvent extends Event
{
    use UserAwareTrait;

    public function __construct(private readonly User $user)
    {
    }
}
