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

    /**
     * @param string $recoverySecretBox
     */
    public function setRecoverySecretBox($recoverySecretBox)
    {
        $this->recoverySecretBox = $recoverySecretBox;
    }

    public function hasRecoverySecretBox(): bool
    {
        return ($this->getRecoverySecretBox()) ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseRecoverySecretBox()
    {
        $this->recoverySecretBox = null;
    }
}
