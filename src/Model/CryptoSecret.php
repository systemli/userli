<?php

namespace App\Model;

use Exception;
use App\Traits\SaltTrait;

class CryptoSecret
{
    use SaltTrait;

    /**
     * CryptoSecret constructor.
     */
    public function __construct(string $salt, private string $nonce, private string $secret)
    {
        $this->salt = $salt;
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function encode(): string
    {
        return base64_encode($this->getSalt().$this->getNonce().$this->getSecret());
    }

    /**
     * @throws Exception
     */
    public static function decode(string $encrypted): self
    {
        $decoded = base64_decode($encrypted, true);

        // check for general failures
        if (false === $decoded) {
            throw new Exception('Base64 decoding of encrypted message failed');
        }

        // check for incomplete message
        if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
            throw new Exception('The encrypted message was truncated');
        }

        $salt = mb_substr($decoded, 0, SODIUM_CRYPTO_PWHASH_SALTBYTES, '8bit');
        $nonce = mb_substr($decoded, SODIUM_CRYPTO_PWHASH_SALTBYTES, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $secret = mb_substr($decoded, SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

        return new CryptoSecret($salt, $nonce, $secret);
    }
}
