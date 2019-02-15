<?php

namespace App\Handler;

use App\Model\CryptoBoxSecret;

class CryptoBoxSecretHandler
{
    /**
     * Using PHP sodium implementation for crypto stuff. Commands taken from:
     * * https://secure.php.net/manual/en/intro.sodium.php#122003
     * * https://www.zimuel.it/slides/phpday2018/sodium
     * * https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use.
     */

    /**
     * @param string $message
     * @param string $password
     *
     * @return CryptoBoxSecret
     * @throws \Exception
     */
    public static function create(string $message, string $password): CryptoBoxSecret
    {
        // generate salt for symmetric encryption key
        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);

        // generate symmetric encryption key from key and salt
        $key = sodium_crypto_pwhash(
            32,
            $password,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );

        // generate a key pair from the symmetric key
        $keyPair = sodium_crypto_box_seed_keypair($key);
        $publicKey = sodium_crypto_box_publickey($keyPair);

        // generate the crypto secret
        $secret = sodium_crypto_box_seal($message, $publicKey);

        // cleanup variables with confidential content
        sodium_memzero($message);
        sodium_memzero($password);
        sodium_memzero($key);
        sodium_memzero($keyPair);

        return new CryptoBoxSecret($publicKey, $salt, $secret);
    }

    /**
     * @param CryptoBoxSecret $cryptoSecret
     * @param string          $password
     *
     * @return string|null
     */
    public static function decrypt(CryptoBoxSecret $cryptoSecret, string $password): ?string
    {
        // generate symmetric encryption key from key and salt
        $key = sodium_crypto_pwhash(
            32,
            strtolower($password),
            $cryptoSecret->getSalt(),
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );

        // generate a key pair from the symmetric key
        $keyPair = sodium_crypto_box_seed_keypair($key);

        // decrypt message
        if (false === $message = sodium_crypto_box_seal_open($cryptoSecret->getSecret(), $keyPair)) {
            return null;
        };

        // cleanup variables with confidential content
        sodium_memzero($password);
        sodium_memzero($key);
        sodium_memzero($keyPair);

        return $message;
    }
}
