<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait RecoverySecretBoxTrait
{
    /** @ORM\Column(type="text", nullable=true) */
    private ?string $recoverySecretBox;

    public function getRecoverySecretBox(): ?string
    {
        return $this->recoverySecretBox;
    }

    public function setRecoverySecretBox(?string $recoverySecretBox): void
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
