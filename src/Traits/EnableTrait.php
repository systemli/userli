<?php

namespace App\Traits;

trait EnableTrait
{
    /**
     * @var bool;
     */
    private $enabled;

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
