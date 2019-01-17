<?php

namespace App\Form\Model;

/**
 * @author doobry <doobry@systemli.org>
 */
class RecoveryTokenAck
{
    /**
     * @var bool
     */
    public $ack = false;

    /**
     * @var string
     */
    private $recoveryToken;

    /**
     * @return string|null
     */
    public function getRecoveryToken(): ?string
    {
        return $this->recoveryToken;
    }

    /**
     * @param string $recoveryToken
     */
    public function setRecoveryToken(string $recoveryToken): void
    {
        $this->recoveryToken = $recoveryToken;
    }
}
