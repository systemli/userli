<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;

interface UpdatedTimeInterface
{
    public function getUpdatedTime(): ?DateTime;

    public function setUpdatedTime(DateTime $updatedTime): void;

    public function updateUpdatedTime(): void;
}
