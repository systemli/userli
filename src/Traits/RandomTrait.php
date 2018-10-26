<?php

namespace App\Traits;

/**
 * @author tim <tim@systemli.org>
 */
trait RandomTrait
{
    /**
     * @var bool;
     */
    private $random;

    /**
     * @return bool
     */
    public function isRandom(): bool
    {
        return (bool) $this->random;
    }

    /**
     * @param bool $random
     */
    public function setRandom($random)
    {
        $this->random = $random;
    }
}
