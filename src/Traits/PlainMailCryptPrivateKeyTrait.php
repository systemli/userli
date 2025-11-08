<?php

declare(strict_types=1);

namespace App\Traits;

/**
 * Trait PlainMailCryptPrivateKeyTrait.
 */
trait PlainMailCryptPrivateKeyTrait
{
    private ?string $plainMailCryptPrivateKey = null;

    public function getPlainMailCryptPrivateKey(): ?string
    {
        return $this->plainMailCryptPrivateKey;
    }

    public function setPlainMailCryptPrivateKey(?string $plainMailCryptPrivateKey): void
    {
        $this->plainMailCryptPrivateKey = $plainMailCryptPrivateKey;
    }

    public function erasePlainMailCryptPrivateKey(): void
    {
        $this->plainMailCryptPrivateKey = null;
    }
}
