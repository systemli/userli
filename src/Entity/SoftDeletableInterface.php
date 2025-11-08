<?php

declare(strict_types=1);

namespace App\Entity;

interface SoftDeletableInterface
{
    public function isDeleted(): bool;
}
