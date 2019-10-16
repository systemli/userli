<?php

namespace App\Traits;

trait DeleteTrait
{
    /**
     * @var bool;
     */
    private $deleted;

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return (bool) $this->deleted;
    }

    /**
     * @return bool
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }
}
