<?php

namespace App\Traits;

trait RandomTrait
{
    /**
     * @var bool;
     */
    private $random;

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
