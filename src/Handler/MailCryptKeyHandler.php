<?php

namespace App\Handler;

use App\Entity\User;
use App\Model\CryptoSecret;
use App\Model\MailCryptKeyPair;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class AliasHandler.
 */
class MailCryptKeyHandler
{
    // Use elliptic curve type 'secp521r1' for mail_crypt keys
    const MAIL_CRYPT_PRIVATE_KEY_TYPE = OPENSSL_KEYTYPE_EC;
    const MAIL_CRYPT_CURVE_NAME = 'secp521r1';

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * MailCryptPrivateKeyHandler constructor.
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
     * @throws \Exception
     */
    public function create(User $user)
    {
        $pKey = openssl_pkey_new([
            'private_key_type' => self::MAIL_CRYPT_PRIVATE_KEY_TYPE,
            'curve_name' => self::MAIL_CRYPT_CURVE_NAME,
        ]);
        openssl_pkey_export($pKey, $privateKey);
        $keyPair = new MailCryptKeyPair(base64_encode($privateKey), base64_encode(openssl_pkey_get_details($pKey)['key']));
        sodium_memzero($privateKey);

        // get plain user password
        if (null === $plainPassword = $user->getPlainPassword()) {
            throw new \Exception('plainPassword should not be null');
        }

        $mailCryptPrivateSecret = CryptoSecretHandler::create($keyPair->getPrivateKey(), $plainPassword);
        $user->setMailCryptPublicKey($keyPair->getPublicKey());
        $user->setMailCryptPrivateSecret($mailCryptPrivateSecret->encode());

        // Clear variables with confidential content from memory
        $keyPair->erase();
        sodium_memzero($plainPassword);

        $this->manager->flush();
    }

    /**
     * @param User   $user
     * @param string $oldPlainPassword
     *
     * @throws \Exception
     */
    public function update(User $user, string $oldPlainPassword)
    {
        // get plain user password to be encrypted
        if (null === $plainPassword = $user->getPlainPassword()) {
            throw new \Exception('plainPassword should not be null');
        }

        if (null === $secret = $user->getMailCryptPrivateSecret()) {
            throw new \Exception('secret should not be null');
        }

        if (null === $privateKey = CryptoSecretHandler::decrypt(CryptoSecret::decode($secret), $oldPlainPassword)) {
            throw new \Exception('decryption of mailCryptPrivateSecret failed');
        }

        $user->setMailCryptPrivateSecret(CryptoSecretHandler::create($privateKey, $plainPassword)->encode());

        // Clear variables with confidential content from memory
        sodium_memzero($plainPassword);
        sodium_memzero($oldPlainPassword);
        sodium_memzero($privateKey);

        $this->manager->flush();
    }
}
