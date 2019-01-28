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
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @return string
     */
    public function encodePrivateKey(): string
    {
        return base64_encode($this->getPrivateKey());
    }

    /**
     * @return string
     */
    public function encodePublicKey(): string
    {
        return base64_encode($this->getPublicKey());
    }

    /**
     * @param string $encoded
     *
     * @return MailCryptKeyPair
     * @throws \Exception
     */
    public static function decodePrivateKey(string $encoded): self
    {
        $decoded = base64_decode($encoded, true);

        // check for general failures
        if (false === $decoded) {
            throw new \Exception('Base64 decoding of encrypted message failed');
        }

        // check for incomplete message
        if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
            throw new \Exception('The encrypted message was truncated');
        }

        $privateKeySecret = mb_substr($decoded, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES + SODIUM_CRYPTO_PWHASH_SALTBYTES, null, '8bit');

        return new MailCryptKeyPair($privateKeySecret, $publicKey);
    }
}
