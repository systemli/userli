<?php

namespace App\Traits;

trait DeleteTrait
{
    /**
     * @var bool;
     */
    private $deleted;

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }
}
