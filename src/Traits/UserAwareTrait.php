<?php

namespace App\Traits;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

trait UserAwareTrait
{
    /** @ORM\ManyToOne(targetEntity="User") */
    private ?User $user;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
