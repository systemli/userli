<?php

namespace App\Traits;

/**
 * @author doobry <doobry@systemli.org>
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
    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    /**
     * @param string|null $publicKey
     */
    public function setPublicKey($publicKey): void
    {
        $this->publicKey = $publicKey;
    }
}
