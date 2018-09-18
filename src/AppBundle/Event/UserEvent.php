<?php

namespace AppBundle\Event;

use AppBundle\Entity\User;
use AppBundle\Traits\UserAwareTrait;
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
