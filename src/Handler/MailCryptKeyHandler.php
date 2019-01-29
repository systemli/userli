<?php

namespace App\Handler;

use App\Entity\User;
use App\Model\CryptoSecret;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class AliasHandler.
 */
class MailCryptKeyHandler
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * MailCryptPrivateKeyHandler constructor.
     *
     * @param ObjectManager           $manager
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
    public function generateKeyPair(): string
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
        $keyPair = $this->generateKeyPair();

        // get plain user password
        if (null === $plainPassword = $user->getPlainPassword()) {
            throw new \Exception('plainPassword should not be null');
        }
        $user->eraseCredentials();

        $mailCryptPrivateSecret = CryptoSecretHandler::create($keyPair->getPrivateKey(), $plainPassword);
        $user->setMailCryptPublicKey($keyPair->getPublicKey()->encode());
        $user->setMailCryptPrivateSecret($mailCryptPrivateSecret->encode());

        // Clear variables with confidential content from memory
        sodium_memzero($keyPair);
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
        if (null === $plainPassword = $user->getPlainPassword()) {
            throw new \Exception('plainPassword should not be null');
        }
        $user->eraseCredentials();

        if (null === $secret = $user->getMailCryptPrivateSecret()) {
            throw new \Exception('secret should not be null');
        }
        $user->setMailCryptPrivateSecret(CryptoSecretHandler::create($secret, $plainPassword)->encode());
    }

    /**
     * @param User   $user
     * @param string $password
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function verify(User $user, string $password): bool
    {
        if (!$user->hasMailCryptPrivateSecret()) {
            return false;
        }

        if (null === $mailCryptPrivateSecretEncoded = $user->getRecoverySecret()) {
            throw new \Exception('mailCryptPrivateSecretEncoded should not be null');
        }

        try {
            $mailCryptPrivateSecret = CryptoSecret::decode($mailCryptPrivateSecretEncoded);
        } catch (\Exception $e) {
            return false;
        }

        return (null !== CryptoSecretHandler::decrypt($mailCryptPrivateSecret, $password)) ? true : false;
    }
}
