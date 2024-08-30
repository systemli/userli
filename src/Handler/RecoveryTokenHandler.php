<?php

namespace App\Handler;

use Exception;
use App\Entity\User;
use App\Model\CryptoSecret;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

/**
 * Class RecoveryTokenHandler.
 */
readonly class RecoveryTokenHandler
{
    /**
     * RecoveryTokenHandler constructor.
     */
    public function __construct(private EntityManagerInterface $manager)
    {
    }

    /**
     * @throws Exception
     */
    public function generateToken(): string
    {
        // generate a version 4 (random) UUID object
        return strtolower(Uuid::uuid4()->toString());
    }

    /**
     * @throws Exception
     */
    public function create(User $user): void
    {
        $recoveryToken = $this->generateToken();

        if (null === $user->getPlainMailCryptPrivateKey()) {
            throw new Exception('plainMailCryptPrivateKey should not be null');
        }

        $recoverySecretBox = CryptoSecretHandler::create($user->getPlainMailCryptPrivateKey(), $recoveryToken);
        $user->setRecoverySecretBox($recoverySecretBox->encode());
        $user->eraseRecoveryStartTime();
        $user->setPlainRecoveryToken($recoveryToken);

        // Clear variables with confidential content from memory
        sodium_memzero($recoveryToken);

        $this->manager->flush();
    }

    /**
     * @throws Exception
     */
    public function verify(User $user, string $recoveryToken): bool
    {
        if (!$user->hasRecoverySecretBox()) {
            return false;
        }
        $recoverySecretBoxEncoded = $user->getRecoverySecretBox();

        try {
            $recoverySecretBox = CryptoSecret::decode($recoverySecretBoxEncoded);
        } catch (Exception) {
            return false;
        }

        return null !== CryptoSecretHandler::decrypt($recoverySecretBox, $recoveryToken);
    }

    /**
     * @throws Exception
     */
    public function decrypt(User $user, string $recoveryToken): string
    {
        if (null === $secret = $user->getRecoverySecretBox()) {
            throw new Exception('secret should not be null');
        }

        if (null === $privateKey = CryptoSecretHandler::decrypt(CryptoSecret::decode($secret), $recoveryToken)) {
            throw new Exception('decryption of recoverySecretBox failed');
        }

        sodium_memzero($recoveryToken);

        return $privateKey;
    }
}
