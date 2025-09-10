<?php

namespace App\Event;

use App\Entity\User;
use App\Traits\UserAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class UserEvent extends Event
{
    use UserAwareTrait;

    public const USER_CREATED = 'user.created';

    public const USER_DELETED = 'user.deleted';

    public const PASSWORD_CHANGED = 'user.password_changed';

    /**
     * Constructor.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
