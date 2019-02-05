<?php

namespace App\Model;

class MailCryptKeyPair
{
    /**
     * @var string
     */
    private $privateKey;
    /**
     * @var string
     */
    private $publicKey;

    /**
     * RecoverySecret constructor.
     *
     * @param string $privateKey
     * @param string $publicKey
     */
    public function __construct(string $privateKey, string $publicKey)
    {
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
    }

    /**
     * @return string|null
     */
    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    /**
     * @return string|null
     */
    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    public function erase()
    {
        sodium_memzero($this->privateKey);
        sodium_memzero($this->publicKey);
    }
}
