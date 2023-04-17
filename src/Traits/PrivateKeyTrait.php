<?php

namespace App\Traits;

trait PrivateKeyTrait
{
    private ?string $privateKey = null;

    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    public function setPrivateKey(?string $privateKey): void
    {
        $this->privateKey = $privateKey;
    }
}
