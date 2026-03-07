<?php

declare(strict_types=1);

namespace App\Traits;

/**
 * Holds a generic private key string (not persisted). Used by DTOs in crypto operations.
 */
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
