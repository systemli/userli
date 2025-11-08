<?php

declare(strict_types=1);

namespace App\Model;

use App\Traits\PrivateKeyTrait;
use App\Traits\PublicKeyTrait;
use SodiumException;

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

    /**
     * @throws SodiumException
     */
    public function erase(): void
    {
        sodium_memzero($this->privateKey);
        sodium_memzero($this->publicKey);
    }
}
