<?php

namespace App\Traits;

trait WkdKeyTrait
{
    /**
     * @var string|null
     */
    public $wkdKey;

    public function getWkdKey(): ?string
    {
        return $this->wkdKey;
    }

    public function setWkdKey(?string $wkdKey): void
    {
        $this->wkdKey = $wkdKey;
    }
}
