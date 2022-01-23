<?php

namespace App\Traits;

trait LastLoginTimeTrait
{
    /**
     * @var \DateTime|null
     */
    private $lastLoginTime;

    public function getLastLoginTime(): ?\DateTime
    {
        return $this->lastLoginTime;
    }

    public function setLastLoginTime(\DateTime $LastLoginTime): void
    {
        $this->lastLoginTime = $LastLoginTime;
    }

    public function updateLastLoginTime(): void
    {
        $this->setLastLoginTime(new \DateTime());
    }
}
