<?php

namespace App\Traits;

use App\Entity\User;

/**
 * @author louis <louis@systemli.org>
 */
trait UserAwareTrait
{
    /**
     * @var User|null
     */
    private $user;

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }
}
