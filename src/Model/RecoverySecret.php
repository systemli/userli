<?php
 
namespace App\Model;
 
class RecoverySecret
{
    /**
     * @var string
     */
    private $publicKey;
    /**
     * @var string
     */
    private $salt;
    /**
     * @var string
     */
    private $secret;

    /**
     * RecoverySecret constructor.
     *
     * @param string $publicKey
     * @param string $salt
     * @param string $secret
     */
    public function __construct(string $publicKey, string $salt, string $secret)
    {
        $this->publicKey = $publicKey;
        $this->salt = $salt;
        $this->secret = $secret;
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
    public function getSalt(): string
    {
        return $this->salt;
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
        return base64_encode($this->getPublicKey().$this->getSalt().$this->getSecret());
    }

    /**
     * @param string $encrypted
     * @return RecoverySecret
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
        if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_BOX_PUBLICKEYBYTES + SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
            throw new \Exception('The encrypted message was truncated');
        }

        $publicKey = mb_substr($decoded, 0, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES, '8bit');
        $salt = mb_substr($decoded, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES, SODIUM_CRYPTO_PWHASH_SALTBYTES, '8bit');
        $secret = mb_substr($decoded, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES + SODIUM_CRYPTO_PWHASH_SALTBYTES, null, '8bit');

        return new RecoverySecret($publicKey, $salt, $secret);
    }

    /**
     * @param string $encrypted
     * @param string $plainPassword
     * @return RecoverySecret
     * @throws \Exception
     */
    public static function reEncrypt(string $encrypted, string $plainPassword): self
    {
        $recoverySecret = self::decode($encrypted);

        $secret = sodium_crypto_box_seal($plainPassword, $recoverySecret->getPublicKey());

        return new RecoverySecret($recoverySecret->getPublicKey(), $recoverySecret->getSalt(), $secret);
    }
}
