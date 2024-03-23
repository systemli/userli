<?php

namespace App\Traits;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait UpdatedTimeTrait
{
    #[ORM\Column]
    private ?DateTime $updatedTime = null;

    public function getUpdatedTime(): ?DateTime
    {
        return $this->updatedTime;
    }

    public function setUpdatedTime(DateTime $updatedTime): void
    {
        $this->updatedTime = $updatedTime;
    }

    #[ORM\PrePersist]
    public function updateUpdatedTime(): void
    {
        $this->setUpdatedTime(new DateTime());
    }
}
