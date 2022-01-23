<?php

namespace App\Traits;

use App\Entity\User;

trait UserAwareTrait
{
    /**
     * @var User|null
     */
    private $user;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
