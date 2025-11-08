<?php

declare(strict_types=1);

namespace App\Traits;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait LastLoginTimeTrait
{
    #[ORM\Column(nullable: true)]
    private ?DateTime $lastLoginTime = null;

    public function getLastLoginTime(): ?DateTime
    {
        return $this->lastLoginTime;
    }

    public function setLastLoginTime(?DateTime $LastLoginTime): void
    {
        $this->lastLoginTime = $LastLoginTime;
    }

    public function updateLastLoginTime(): void
    {
        $this->setLastLoginTime(new DateTime());
    }
}
