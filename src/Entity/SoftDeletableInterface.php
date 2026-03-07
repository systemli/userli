<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Implemented by entities that support soft deletion.
 *
 * Soft-deleted entities are flagged as deleted rather than removed from the database,
 * used to prevent identity theft after deletion of users or aliases.
 */
interface SoftDeletableInterface
{
    public function isDeleted(): bool;
}
