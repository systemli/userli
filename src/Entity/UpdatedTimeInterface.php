<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;

/**
 * Implemented by entities that track their last modification time.
 *
 * The updatedTime field is automatically maintained via a Doctrine lifecycle callback.
 */
interface UpdatedTimeInterface
{
    public function getUpdatedTime(): ?DateTimeImmutable;

    public function setUpdatedTime(DateTimeImmutable $updatedTime): void;

    public function updateUpdatedTime(): void;
}
