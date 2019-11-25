<?php

namespace App\Entity;

interface SoftDeletableInterface
{
    public function isDeleted(): bool;
}
