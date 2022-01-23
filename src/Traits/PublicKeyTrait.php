<?php

namespace App\Traits;

trait PublicKeyTrait
{
    /**
     * @var string|null
     */
    public $publicKey;

    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    public function setPublicKey(?string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }
}
