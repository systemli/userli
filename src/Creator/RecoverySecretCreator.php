<?php
 
namespace App\Creator;
 
use App\Model\RecoverySecret;
 
class RecoverySecretCreator
{
    /**
     * Using PHP sodium implementation for crypto stuff. Commands taken from:
     * * https://secure.php.net/manual/en/intro.sodium.php#122003
     * * https://www.zimuel.it/slides/phpday2018/sodium
     * * https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use
     */

    /**
     * @param string $plainPassword
     * @param string $recoveryToken
     *
     * @return RecoverySecret
     * @throws \Exception
     */
    public static function create(string $plainPassword, string $recoveryToken): RecoverySecret
    {
        // generate salt for symmetric encryption key
        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
 
        // generate symmetric encryption key from key and salt
        $key = sodium_crypto_pwhash(
            32,
            $recoveryToken,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
 
        // generate a key pair from the symmetric key
        $keyPair = sodium_crypto_box_seed_keypair($key);
        $publicKey = sodium_crypto_box_publickey($keyPair);
 
        // generate the recovery secret
        $secret = sodium_crypto_box_seal($plainPassword, $publicKey);
 
        // cleanup variables with confidential content
        sodium_memzero($plainPassword);
        sodium_memzero($recoveryToken);
        sodium_memzero($key);
        sodium_memzero($keyPair);
 
        return new RecoverySecret($publicKey, $salt, $secret);
    }
}
