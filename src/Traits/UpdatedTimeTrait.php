<?php

declare(strict_types=1);

namespace App\Traits;

use App\Entity\UpdatedTimeInterface;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @see UpdatedTimeInterface
 */
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

    public function updateUpdatedTime(): void
    {
        $this->setUpdatedTime(new DateTime());
    }
}
