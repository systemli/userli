<?php

namespace App\Form\Model;

/**
 * @author doobry <doobry@systemli.org>
 */
class RecoveryResetPassword
{
    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $recoveryToken;

    /**
     * @var string
     */
    public $newPassword;

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

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
