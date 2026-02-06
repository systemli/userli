<?php

declare(strict_types=1);

namespace App\Traits;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

trait CreationTimeTrait
{
    #[ORM\Column]
    private ?DateTimeImmutable $creationTime = null;

    public function getCreationTime(): ?DateTimeImmutable
    {
        return $this->creationTime;
    }

    public function setCreationTime(DateTimeImmutable $creationTime): void
    {
        $this->creationTime = $creationTime;
    }
}
