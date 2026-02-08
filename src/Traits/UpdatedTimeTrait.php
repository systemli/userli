<?php

declare(strict_types=1);

namespace App\Traits;

use App\Entity\UpdatedTimeInterface;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @see UpdatedTimeInterface
 */
trait UpdatedTimeTrait
{
    #[ORM\Column]
    private ?DateTimeImmutable $updatedTime = null;

    public function getUpdatedTime(): ?DateTimeImmutable
    {
        return $this->updatedTime;
    }

    public function setUpdatedTime(DateTimeImmutable $updatedTime): void
    {
        $this->updatedTime = $updatedTime;
    }

    public function updateUpdatedTime(): void
    {
        $this->setUpdatedTime(new DateTimeImmutable());
    }
}
