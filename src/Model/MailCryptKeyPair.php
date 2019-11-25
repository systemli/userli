<?php

namespace App\Model;

use App\Traits\PrivateKeyTrait;
use App\Traits\PublicKeyTrait;

class MailCryptKeyPair
{
    use PrivateKeyTrait;
    use PublicKeyTrait;

    /**
     * MailCryptKeyPair constructor.
     */
    public function __construct(string $privateKey, string $publicKey)
    {
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
    }

    public function erase()
    {
        sodium_memzero($this->privateKey);
        sodium_memzero($this->publicKey);
    }
}
