<?php

namespace App\Handler;

use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

/**
 * Class AliasHandler.
 */
class RecoveryTokenHandler
{
    /**
     * Using PHP sodium implementation for crypto stuff. Commands taken from:
     * * https://secure.php.net/manual/en/intro.sodium.php#122003
     * * https://www.zimuel.it/slides/phpday2018/sodium
     * * https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use
     */

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * AliasHandler constructor.
     *
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    public function tokenGenerate()
    {
        // generate a version 4 (random) UUID object
        return(Uuid::uuid4()->toString());
    }

    /**
     * @param string $encrypted
     *
     * @return array
     * @throws \Exception
     */
    public function cipherDecode(string $encrypted)
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

        // derive pubkey, salt and encrypted cipher text from $decoded
        return [
            'pubKey' => mb_substr($decoded, 0, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES, '8bit'),
            'keySalt' => mb_substr($decoded, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES, SODIUM_CRYPTO_PWHASH_SALTBYTES, '8bit'),
            'cipherText' => mb_substr($decoded, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES + SODIUM_CRYPTO_PWHASH_SALTBYTES, null, '8bit'),

        ];
    }

    /**
     * @param string $plainPassword
     * @param string $recoveryToken
     *
     * @return string
     * @throws \Exception
     */
    public function tokenEncrypt(string $plainPassword, string $recoveryToken)
    {
        // generate salt for symmetric encryption key
        $keySalt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);

        // generate symmetric encryption key from key and salt
        $key = sodium_crypto_pwhash(
            32,
            $recoveryToken,
            $keySalt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );

        // generate a key pair from the symmetric key
        $keyPair = sodium_crypto_box_seed_keypair($key);
        $pubKey = sodium_crypto_box_publickey($keyPair);

        // encrypt message
        $cipher = base64_encode($pubKey . $keySalt . sodium_crypto_box_seal($plainPassword, $pubKey));

        // cleanup variables with confidential content
        sodium_memzero($plainPassword);
        sodium_memzero($recoveryToken);
        sodium_memzero($key);
        sodium_memzero($keyPair);

        return $cipher;
    }

    /**
     * @param string $encrypted
     * @param string $plainPassword
     *
     * @return string
     * @throws \Exception
     */
    public function tokenReencrypt(string $encrypted, string $plainPassword)
    {
        $decodedCipher = $this->cipherDecode($encrypted);

        // encrypt message
        $cipher = base64_encode($decodedCipher['pubKey'] . $decodedCipher['keySalt'] . sodium_crypto_box_seal($plainPassword, $decodedCipher['pubKey']));

        // cleanup variables with confidential content
        sodium_memzero($encrypted);
        sodium_memzero($plainPassword);

        return $cipher;
    }

    /**
     * @param string $encrypted
     * @param string $recoveryToken
     *
     * @return bool|string
     * @throws \Exception
     */
    public function tokenDecrypt(string $encrypted, string $recoveryToken)
    {
        $decodedCipher = $this->cipherDecode($encrypted);

        // generate symmetric encryption key from key and salt
        $key = sodium_crypto_pwhash(
            32,
            $recoveryToken,
            $decodedCipher['keySalt'],
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );

        // generate a key pair from the symmetric key
        $keyPair = sodium_crypto_box_seed_keypair($key);

        // decrypt message
        $message = sodium_crypto_box_seal_open($decodedCipher['cipherText'], $keyPair);

        // cleanup variables with confidential content
        sodium_memzero($encrypted);
        sodium_memzero($recoveryToken);
        sodium_memzero($key);
        sodium_memzero($keyPair);

        return $message;
    }

    /**
     * @param User $user
     *
     * @return string
     * @throws \Exception
     */
    public function create(User $user)
    {
        $recoveryToken = $this->tokenGenerate();

        // get plain user password to be encrypted
        $plainPassword = $user->getPlainPassword();
        $user->eraseCredentials();

        $cipher = $this->tokenEncrypt($plainPassword, $recoveryToken);
        $user->setRecoveryCipher($cipher);

        // Clear variables with confidential content from memory
        sodium_memzero($plainPassword);

        $this->manager->flush();

        return $recoveryToken;
    }

    /**
     * @param User $user
     *
     * @throws \Exception
     */
    public function update(User $user)
    {
        // get plain user password to be encrypted
        $plainPassword = $user->getPlainPassword();
        $user->eraseCredentials();

        $cipher = $user->getRecoveryCipher();
        $user->setRecoveryCipher($this->tokenReencrypt($cipher, $plainPassword));
    }
}
