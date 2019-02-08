<?php

namespace App\Traits;

/**
 * Trait PlainMailCryptPrivateKeyTrait.
 */
trait PlainMailCryptPrivateKeyTrait
{
    /**
     * @var string|null
     */
    private $plainMailCryptPrivateKey;

    /**
     * @return string|null
     */
    public function getPlainMailCryptPrivateKey()
    {
        return $this->plainMailCryptPrivateKey;
    }

    /**
     * @param string|null $plainMailCryptPrivateKey
     */
    public function setPlainMailCryptPrivateKey($plainMailCryptPrivateKey)
    {
        $this->plainMailCryptPrivateKey = $plainMailCryptPrivateKey;
    }

    /**
     * {@inheritdoc}
     */
    public function erasePlainMailCryptPrivateKey()
    {
        $this->plainMailCryptPrivateKey = null;
    }
}
