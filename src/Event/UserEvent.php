<?php

namespace App\Event;

use App\Entity\User;
use App\Traits\UserAwareTrait;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author louis <louis@systemli.org>
 */
class UserEvent extends Event
{
    use UserAwareTrait;

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
