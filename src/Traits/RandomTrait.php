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
        return $this->random;
    }

    public function setRandom(bool $random): void
    {
        $this->random = $random;
    }
}
