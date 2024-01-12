<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait LastLoginTimeTrait
{
    /** @ORM\Column(nullable=true) */
    private ?\DateTime $lastLoginTime = null;

    public function getLastLoginTime(): ?\DateTime
    {
        return $this->lastLoginTime;
    }

    public function setLastLoginTime(?\DateTime $LastLoginTime): void
    {
        $this->lastLoginTime = $LastLoginTime;
    }

    public function updateLastLoginTime(): void
    {
        $this->setLastLoginTime(new \DateTime());
    }
}
