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
     * @return null|string
     */
    public function getPlainMailCryptPrivateKey()
    {
        return $this->plainMailCryptPrivateKey;
    }

    /**
     * @param null|string $plainMailCryptPrivateKey
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
