<?php

namespace App\Traits;

trait RecoveryTokenTrait
{
    private ?string $recoveryToken = null;

    public function getRecoveryToken(): ?string
    {
        return $this->recoveryToken;
    }

    public function setRecoveryToken(?string $recoveryToken): void
    {
        $this->recoveryToken = $recoveryToken;
    }
}
