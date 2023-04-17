<?php

namespace App\Traits;

trait PublicKeyTrait
{
    /**
     * @var string|null
     */
    public ?string $publicKey = null;

    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    public function setPublicKey(?string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }
}
