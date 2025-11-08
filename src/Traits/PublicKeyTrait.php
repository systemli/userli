<?php

declare(strict_types=1);

namespace App\Traits;

trait PublicKeyTrait
{
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
