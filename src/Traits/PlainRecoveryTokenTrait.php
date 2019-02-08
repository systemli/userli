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
     * @return string|null
     */
    public function getPlainRecoveryToken()
    {
        return $this->plainRecoveryToken;
    }

    /**
     * @param string|null $plainRecoveryToken
     */
    public function setPlainRecoveryToken($plainRecoveryToken)
    {
        $this->plainRecoveryToken = $plainRecoveryToken;
    }

    /**
     * {@inheritdoc}
     */
    public function erasePlainRecoveryToken()
    {
        $this->plainRecoveryToken = null;
    }
}
