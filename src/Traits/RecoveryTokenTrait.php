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

    /**
     * @param string $recoveryToken
     */
    public function setRecoveryToken($recoveryToken)
    {
        $this->recoveryToken = $recoveryToken;
    }
}
