<?php

declare(strict_types=1);

namespace App\Traits;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

trait LastLoginTimeTrait
{
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastLoginTime = null;

    public function getLastLoginTime(): ?DateTimeImmutable
    {
        return $this->lastLoginTime;
    }

    public function setLastLoginTime(?DateTimeImmutable $lastLoginTime): void
    {
        $this->lastLoginTime = $lastLoginTime;
    }

    public function updateLastLoginTime(): void
    {
        $this->setLastLoginTime(new DateTimeImmutable());
    }
}
