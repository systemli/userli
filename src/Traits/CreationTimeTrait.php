<?php

declare(strict_types=1);

namespace App\Traits;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait CreationTimeTrait
{
    #[ORM\Column]
    private ?DateTime $creationTime = null;

    public function getCreationTime(): ?DateTime
    {
        return $this->creationTime;
    }

    public function setCreationTime(DateTime $creationTime): void
    {
        $this->creationTime = $creationTime;
    }

    #[ORM\PrePersist]
    public function updateCreationTime(): void
    {
        if (null === $this->creationTime) {
            $this->setCreationTime(new DateTime());
        }
    }
}
