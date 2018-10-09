<?php

namespace App\Traits;

/**
 * @author louis <louis@systemli.org>
 */
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
