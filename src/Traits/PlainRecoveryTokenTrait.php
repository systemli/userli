<?php

namespace App\Traits;

/**
 * Trait PlainRecoveryTokenTrait.
 */
trait PlainRecoveryTokenTrait
{
    /**
     * @var string|null
     */
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
