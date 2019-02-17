<?php

namespace App\Traits;

/**
 * @author louis <louis@systemli.org>
 */
trait PublicKeyTrait
{
    /**
     * @var string|null
     */
    public $publicKey;

    /**
     * @return string|null
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @param string|null $publicKey
     */
    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;
    }
}
