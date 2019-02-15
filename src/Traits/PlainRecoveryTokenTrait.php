<?php

namespace App\Traits;

/**
 * Trait PlainRecoveryTokenTrait.
 */
trait PlainRecoveryTokenTrait
{
    /**
     * @var string|null
     */
    private $plainRecoveryToken;

    /**
     * @return null|string
     */
    public function getPlainRecoveryToken()
    {
        return $this->plainRecoveryToken;
    }

    /**
     * @param null|string $plainRecoveryToken
     */
    public function setPlainRecoveryToken($plainRecoveryToken)
    {
        $this->plainRecoveryToken = $plainRecoveryToken;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseToken()
    {
        $this->plainRecoveryToken = null;
    }
}
