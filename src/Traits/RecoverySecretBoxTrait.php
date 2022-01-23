<?php

namespace App\Traits;

trait RecoverySecretBoxTrait
{
    /**
     * @var string|null
     */
    private $recoverySecretBox;

    public function getRecoverySecretBox(): ?string
    {
        return $this->recoverySecretBox;
    }

    public function setRecoverySecretBox(string $recoverySecretBox): void
    {
        $this->recoverySecretBox = $recoverySecretBox;
    }

    public function hasRecoverySecretBox(): bool
    {
        return (bool) $this->getRecoverySecretBox();
    }

    public function eraseRecoverySecretBox(): void
    {
        $this->recoverySecretBox = null;
    }
}
