<?php

namespace App\Traits;

/**
 * @author doobry <doobry@systemli.org>
 */
trait RecoverySecretTrait
{
    /**
     * @var string|null
     */
    private $recoverySecret;

    /**
     * @return string|null
     */
    public function getRecoverySecret(): ?string
    {
        return $this->recoverySecret;
    }

    /**
     * @param string $recoverySecret
     */
    public function setRecoverySecret($recoverySecret)
    {
        $this->recoverySecret = $recoverySecret;
    }

    /**
     * @return bool
     */
    public function hasRecoverySecret(): bool
    {
        return ($this->getRecoverySecret()) ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseRecoverySecret()
    {
        $this->recoverySecret = null;
    }
}
