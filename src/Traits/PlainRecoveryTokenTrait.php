<?php

declare(strict_types=1);

namespace App\Traits;

/**
 * Holds the plaintext recovery token in memory (not persisted). Only available at generation time.
 */
trait PlainRecoveryTokenTrait
{
    private ?string $plainRecoveryToken = null;

    public function getPlainRecoveryToken(): ?string
    {
        return $this->plainRecoveryToken;
    }

    public function setPlainRecoveryToken(?string $plainRecoveryToken): void
    {
        $this->plainRecoveryToken = $plainRecoveryToken;
    }

    public function erasePlainRecoveryToken(): void
    {
        $this->plainRecoveryToken = null;
    }
}
