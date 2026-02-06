<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;

interface UpdatedTimeInterface
{
    public function getUpdatedTime(): ?DateTimeImmutable;

    public function setUpdatedTime(DateTimeImmutable $updatedTime): void;

    public function updateUpdatedTime(): void;
}
