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

    /**
     * @param User $user
     *
     * @return bool
     */
    public function hasToken(User $user)
    {
        return (empty($user->getRecoveryCipher())) ? false : true;
    }

    /**
     * @param string $plainPassword
     * @param string $recoveryToken
     *
     * @return string
     * @throws \Exception
     */
    private function tokenEncryptAsymmetric(string $plainPassword, string $recoveryToken)
    {
        // use php sodium implementation for crypto stuff
        // commands taken from:
        // * https://secure.php.net/manual/en/intro.sodium.php#122003
        // * https://www.zimuel.it/slides/phpday2018/sodium

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
    private function tokenReencryptAsymmetric(string $encrypted, string $plainPassword)
    {
        // use php sodium implementation for crypto stuff
        // commands taken from:
        // * https://secure.php.net/manual/en/intro.sodium.php#122003
        // * https://www.zimuel.it/slides/phpday2018/sodium

        $decoded = base64_decode($encrypted);

        // check for general failures
        if (false === $decoded) {
            throw new \Exception('Base64 decoding of encrypted message failed');
        }

        // check for incomplete message
        if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_BOX_PUBLICKEYBYTES + SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
            throw new \Exception('The encrypted message was truncated');
        }

        // derive pubkey, salt and encrypted cipher text from $encrypted
        $pubKey = mb_substr($decoded, 0, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES, '8bit');
        $keySalt = mb_substr($decoded, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES, SODIUM_CRYPTO_PWHASH_SALTBYTES, '8bit');
        $cipherText = mb_substr($decoded, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES + SODIUM_CRYPTO_PWHASH_SALTBYTES, null, '8bit');

        // encrypt message
        $cipher = base64_encode($pubKey . $keySalt . sodium_crypto_box_seal($plainPassword, $pubKey));

        // cleanup variables with confidential content
        sodium_memzero($encrypted);
        sodium_memzero($plainPassword);
        sodium_memzero($decoded);
        sodium_memzero($cipherText);

        return $cipher;
    }

    /**
     * @param string $encrypted
     * @param string $recoveryToken
     *
     * @return bool|string
     * @throws \Exception
     */
    private function tokenDecryptAsymmetric(string $encrypted, string $recoveryToken)
    {
        // use php sodium implementation for crypto stuff
        // commands taken from:
        // * https://secure.php.net/manual/en/intro.sodium.php#122003
        // * https://www.zimuel.it/slides/phpday2018/sodium

        $decoded = base64_decode($encrypted);

        // check for general failures
        if (false === $decoded) {
            throw new \Exception('Base64 decoding of encrypted message failed');
        }

        // check for incomplete message
        if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_BOX_PUBLICKEYBYTES + SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
            throw new \Exception('The encrypted message was truncated');
        }

        // derive pubkey, salt and encrypted cipher text from $encrypted
        $pubKey = mb_substr($decoded, 0, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES, '8bit');
        $keySalt = mb_substr($decoded, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES, SODIUM_CRYPTO_PWHASH_SALTBYTES, '8bit');
        $cipherText = mb_substr($decoded, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES + SODIUM_CRYPTO_PWHASH_SALTBYTES, null, '8bit');

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

        // decrypt message
        $message = sodium_crypto_box_seal_open($cipherText, $keyPair);

        // check for decryption failures
        if (false === $message) {
            throw new \Exception('The encrypted message was tampered with in transit');
        }

        // cleanup variables with confidential content
        sodium_memzero($encrypted);
        sodium_memzero($recoveryToken);
        sodium_memzero($decoded);
        sodium_memzero($cipherText);
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
        // generate a version 4 (random) UUID object
        $recoveryToken = Uuid::uuid4()->toString();

        // get plain user password to be encrypted
        $plainPassword = $user->getPlainPassword();
        $user->eraseCredentials();

        $cipher = $this->tokenEncryptAsymmetric($plainPassword, $recoveryToken);
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
        $user->setRecoveryCipher($this->tokenReencryptAsymmetric($cipher, $plainPassword));
    }
}
