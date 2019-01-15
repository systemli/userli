<?php

namespace App\Form\Model;

/**
 * @author doobry <doobry@systemli.org>
 */
class RegistrationRecoveryToken
{
    /**
     * @var bool
     */
    public $ack = false;

    /**
     * @var string
     */
    public $recoveryToken = '';

    /**
     * @return string
     */
    public function getRecoveryToken(): string
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
