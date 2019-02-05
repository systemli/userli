<?php

namespace App\Model;

class CryptoSecret
{
    /**
     * @var string
     */
    private $salt;
    /**
     * @var string
     */
    private $nonce;
    /**
     * @var string
     */
    private $secret;

    /**
     * RecoverySecret constructor.
     *
     * @param string $salt
     * @param string $nonce
     * @param string $secret
     */
    public function __construct(string $salt, string $nonce, string $secret)
    {
        $this->salt = $salt;
        $this->nonce = $nonce;
        $this->secret = $secret;
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * @return string
     */
    public function getNonce(): string
    {
        return $this->nonce;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @return string
     */
    public function encode(): string
    {
        return base64_encode($this->getSalt().$this->getNonce().$this->getSecret());
    }

    /**
     * @param string $encrypted
     *
     * @return CryptoSecret
     *
     * @throws \Exception
     */
    public static function decode(string $encrypted): self
    {
        $decoded = base64_decode($encrypted, true);

        // check for general failures
        if (false === $decoded) {
            throw new \Exception('Base64 decoding of encrypted message failed');
        }

        // check for incomplete message
        if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
            throw new \Exception('The encrypted message was truncated');
        }

        $salt = mb_substr($decoded, 0, SODIUM_CRYPTO_PWHASH_SALTBYTES, '8bit');
        $nonce = mb_substr($decoded, SODIUM_CRYPTO_PWHASH_SALTBYTES, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $secret = mb_substr($decoded, SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

        return new CryptoSecret($salt, $nonce, $secret);
    }
}
