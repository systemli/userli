<?php

namespace App\Handler;

use App\Entity\User;
use App\Model\RecoverySecret;
use Doctrine\Common\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

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
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * RecoveryTokenHandler constructor.
     *
     * @param ObjectManager           $manager
     * @param EncoderFactoryInterface $encoderFactory
     */
    public function __construct(ObjectManager $manager, EncoderFactoryInterface $encoderFactory)
    {
        $this->manager = $manager;
        $this->encoderFactory = $encoderFactory;
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
    public function create(User $user)
    {
        $recoveryToken = $this->generateToken();

        // get plain user password to be encrypted
        $plainPassword = $user->getPlainPassword();
        $user->eraseCredentials();

        $recoverySecret = RecoverySecretHandler::create($plainPassword, $recoveryToken);
        $user->setRecoverySecret($recoverySecret->encode());
        $user->eraseRecoveryStartTime();
        $user->setPlainRecoveryToken($recoveryToken);

        // Clear variables with confidential content from memory
        sodium_memzero($recoveryToken);
        sodium_memzero($plainPassword);

        $this->manager->flush();
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

        $secret = $user->getRecoverySecret();
        $user->setRecoverySecret(RecoverySecret::reEncrypt($secret, $plainPassword)->encode());
        $user->eraseRecoveryStartTime();
    }

    /**
     * @param User   $user
     * @param string $recoveryToken
     *
     * @return bool
     */
    public function verify(User $user, string $recoveryToken): bool
    {
        if (!$user->hasRecoverySecret()) {
            return false;
        }

        try {
            $recoverySecret = RecoverySecret::decode($user->getRecoverySecret());
        } catch (\Exception $e) {
            return false;
        }
        $decrypted = RecoverySecretHandler::decrypt($recoverySecret, $recoveryToken);

        $encoder = $this->encoderFactory->getEncoder($user);
        if (empty($decrypted) || !$encoder->isPasswordValid($user->getPassword(), $decrypted, $user->getSalt())) {
            sodium_memzero($decrypted);

            return false;
        }

        sodium_memzero($decrypted);

        return true;
    }
}
