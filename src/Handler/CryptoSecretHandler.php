<?php

namespace App\Handler;

use App\Model\CryptoSecret;

class CryptoSecretHandler
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
     * @return CryptoSecret
     * @throws \Exception
     */
    public static function create(string $message, string $password): CryptoSecret
    {
        // generate salt for symmetric encryption key
        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);

        // generate symmetric encryption key from password and salt
        $key = sodium_crypto_pwhash(
            32,
            $password,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        // generate the crypto secret
        $secret = sodium_crypto_secretbox($message, $nonce, $key);

        // cleanup variables with confidential content
        sodium_memzero($message);
        sodium_memzero($password);
        sodium_memzero($key);

        return new CryptoSecret($salt, $nonce, $secret);
    }

    /**
     * @param CryptoSecret $cryptoSecret
     * @param string       $password
     *
     * @return string|null
     */
    public static function decrypt(CryptoSecret $cryptoSecret, string $password): ?string
    {
        // generate symmetric encryption key from key and salt
        $key = sodium_crypto_pwhash(
            32,
            strtolower($password),
            $cryptoSecret->getSalt(),
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );

        // decrypt message
        if (false === $message = sodium_crypto_secretbox_open($cryptoSecret->getSecret(), $cryptoSecret->getNonce(), $key)) {
            return null;
        };

        // cleanup variables with confidential content
        sodium_memzero($password);
        sodium_memzero($key);

        return $message;
    }
}
