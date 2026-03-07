<?php

declare(strict_types=1);

namespace App\Traits;

/**
 * Holds the decrypted MailCrypt private key in memory (not persisted). Used transiently during key operations.
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
