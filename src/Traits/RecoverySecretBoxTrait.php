<?php

namespace App\Traits;

trait RecoverySecretBoxTrait
{
    /**
     * @var string|null
     */
    private $recoverySecretBox;

    /**
     * @return string|null
     */
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

    /**
     * @return bool
     */
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
