<?php

namespace App\Traits;

/**
 * Trait PlainMailCryptPrivateKeyTrait.
 */
trait PlainMailCryptPrivateKeyTrait
{
    /**
     * @var string|null
     */
    private $plainMailCryptPrivateKey;

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
