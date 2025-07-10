<?php

namespace App\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait RecoverySecretBoxTrait
{
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $recoverySecretBox = null;

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
