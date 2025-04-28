<?php

namespace App\Event;

use App\Entity\User;
use App\Traits\UserAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class UserCreatedEvent extends Event
{
    use UserAwareTrait;

    public const ADMIN = 'user_created.admin';
    public const REGISTRATION = 'user_created.registration';
    public const RESTORE = 'user_created.restore';

    public function __construct(private readonly User $user)
    {
    }
}
