<?php

namespace App\Traits;

trait RecoveryTokenTrait
{
    /**
     * @var string|null
     */
    private $recoveryToken;

    public function getRecoveryToken(): ?string
    {
        return $this->recoveryToken;
    }

    public function setRecoveryToken(string $recoveryToken): void
    {
        $this->recoveryToken = $recoveryToken;
    }
}
