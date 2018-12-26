<?php

namespace App\Handler;

use App\Creator\RecoverySecretCreator;
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
     * @throws \Exception
     */
    public function generateToken(): string
    {
        // generate a version 4 (random) UUID object
        return strtolower(Uuid::uuid4()->toString());
    }

    /**
     * @param RecoverySecret $recoverySecret
     * @param string         $recoveryToken
     *
     * @return string
     */
    public function decryptToken(RecoverySecret $recoverySecret, string $recoveryToken): string
    {
        // generate symmetric encryption key from key and salt
        $key = sodium_crypto_pwhash(
            32,
            $recoveryToken,
            $recoverySecret->getSalt(),
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );

        // generate a key pair from the symmetric key
        $keyPair = sodium_crypto_box_seed_keypair($key);

        // decrypt message
        $message = sodium_crypto_box_seal_open($recoverySecret->getSecret(), $keyPair);

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
    public function create(User $user): string
    {
        $recoveryToken = $this->generateToken();

        // get plain user password to be encrypted
        $plainPassword = $user->getPlainPassword();
        $user->eraseCredentials();

        $recoverySecret = RecoverySecretCreator::create($plainPassword, $recoveryToken);
        $user->setRecoverySecret($recoverySecret->encode());

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

        $secret = $user->getRecoverySecret();
        $user->setRecoverySecret(RecoverySecret::reEncrypt($secret, $plainPassword)->encode());
    }

    /**
     * @param User   $user
     * @param string $recoveryToken
     *
     * @return bool
     */
    public function verify(User $user, string $recoveryToken): bool
    {
        if (! $user->hasRecoverySecret()) {
            return false;
        }

        try {
            $recoverySecret = RecoverySecret::decode($user->getRecoverySecret());
        } catch (\Exception $e) {
            return false;
        }
        //$decrypted = $this->decryptToken($user->getRecoverySecret(), strtolower($recoveryToken));
        $decrypted = $this->decryptToken($recoverySecret, strtolower($recoveryToken));

        $encoder = $this->encoderFactory->getEncoder($user);
        if (!$encoder->isPasswordValid($user->getPassword(), $decrypted, $user->getSalt())) {
            sodium_memzero($decrypted);
            return false;
        }

        sodium_memzero($decrypted);
        return true;
    }
}
