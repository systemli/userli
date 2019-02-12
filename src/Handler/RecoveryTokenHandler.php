<?php

namespace App\Handler;

use App\Entity\User;
use App\Model\CryptoSecret;
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
     * RecoveryTokenHandler constructor.
     *
     * @param ObjectManager       $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function generateToken(): string
    {
        // generate a version 4 (random) UUID object
        return strtolower(Uuid::uuid4()->toString());
    }

    /**
     * @param User $user
     *
     * @throws \Exception
     */
    public function create(User $user): void
    {
        $recoveryToken = $this->generateToken();

        // get plain user password to be encrypted
        if (null === $plainPassword = $user->getPlainPassword()) {
            throw new \Exception('plainPassword should not be null');
        }
        $user->eraseCredentials();

        if (null === $user->getPlainMailCryptPrivateKey()) {
            throw new \Exception('plainMailCryptPrivateKey should not be null');
        }

        $recoverySecret = CryptoSecretHandler::create($user->getPlainMailCryptPrivateKey(), $recoveryToken);
        $user->setRecoverySecret($recoverySecret->encode());
        $user->eraseRecoveryStartTime();
        $user->setPlainRecoveryToken($recoveryToken);

        // Clear variables with confidential content from memory
        sodium_memzero($recoveryToken);
        sodium_memzero($plainPassword);

        $this->manager->flush();
    }

    /**
     * @param User   $user
     * @param string $recoveryToken
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function verify(User $user, string $recoveryToken): bool
    {
        if (!$user->hasRecoverySecret()) {
            return false;
        }
        $recoverySecretEncoded = $user->getRecoverySecret();

        try {
            $recoverySecret = CryptoSecret::decode($recoverySecretEncoded);
        } catch (\Exception $e) {
            return false;
        }

        return (null !== CryptoSecretHandler::decrypt($recoverySecret, $recoveryToken)) ? true : false;
    }

    /**
     * @param User   $user
     * @param string $recoveryToken
     *
     * @return string
     * @throws \Exception
     */
    public function decrypt(User $user, string $recoveryToken): string
    {
        if (null === $secret = $user->getRecoverySecret()) {
            throw new \Exception('secret should not be null');
        }

        if (null === $privateKey = CryptoSecretHandler::decrypt(CryptoSecret::decode($secret), $recoveryToken)) {
            throw new \Exception('decryption of recoverySecret failed');
        }

        sodium_memzero($recoveryToken);
        return $privateKey;
    }
}
