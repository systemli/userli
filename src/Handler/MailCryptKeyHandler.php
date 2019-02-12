<?php

namespace App\Handler;

use App\Entity\User;
use App\Model\CryptoSecret;
use App\Model\MailCryptKeyPair;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * Class AliasHandler.
 */
class MailCryptKeyHandler
{
    // Use elliptic curve type 'secp521r1' for MailCrypt keys
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
     * @param string $privateKey
     *
     * @return string
     *
     * @throws \Exception
     */
    public function toPkcs8(string $privateKey): string
    {
        // Unfortunately, there doesn't seem to be a way to transform elliptic curve
        // keys from from traditional PEM to PKCS#8 format within PHP yet. The OpenSSL
        // extension doesn't support PKCS#8 format and phpseclib doesn't support elliptic
        // curves.
        //
        // Invoke `openssl pkey` system process to transform the EC key to PKCS#8 format

        $process = new Process(['openssl', 'pkey']);
        $inputStream = new InputStream();
        $process->setInput($inputStream);
        $process->start();
        $inputStream->write($privateKey);
        $inputStream->close();
        $process->wait();

        sodium_memzero($privateKey);

        if (!$process->isSuccessful()) {
            throw new \Exception('Transforming key to PKCS#8 with OpenSSL failed. OpenSSL exited unsuccessfully: ' . $process->getErrorOutput());
        }
        if (substr($process->getOutput(), 0, 27) !== '-----BEGIN PRIVATE KEY-----') {
            throw new \Exception('Transforming key to PKCS#8 with OpenSSL failed. OpenSSL output is no valid PKCS#8 key: ' . $process->getOutput());
        }

        return $process->getOutput();
    }

    /**
     * @param User $user
     *
     * @throws \Exception
     */
    public function create(User $user): void
    {
        $pKey = openssl_pkey_new([
            'private_key_type' => self::MAIL_CRYPT_PRIVATE_KEY_TYPE,
            'curve_name' => self::MAIL_CRYPT_CURVE_NAME,
        ]);
        openssl_pkey_export($pKey, $privateKey);
        $privateKey = base64_encode($this->toPkcs8($privateKey));
        $keyPair = new MailCryptKeyPair($privateKey, base64_encode(openssl_pkey_get_details($pKey)['key']));
        sodium_memzero($privateKey);

        // get plain user password
        if (null === $plainPassword = $user->getPlainPassword()) {
            throw new \Exception('plainPassword should not be null');
        }

        $mailCryptPrivateSecret = CryptoSecretHandler::create($keyPair->getPrivateKey(), $plainPassword);
        $user->setMailCryptPublicKey($keyPair->getPublicKey());
        $user->setMailCryptPrivateSecret($mailCryptPrivateSecret->encode());
        $user->setPlainMailCryptPrivateKey($keyPair->getPrivateKey());

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
    public function update(User $user, string $oldPlainPassword): void
    {
        if (null === $secret = $user->getMailCryptPrivateSecret()) {
            throw new \Exception('secret should not be null');
        }

        if (null === $privateKey = CryptoSecretHandler::decrypt(CryptoSecret::decode($secret), $oldPlainPassword)) {
            throw new \Exception('decryption of mailCryptPrivateSecret failed');
        }

        $this->updateWithPrivateKey($user, $privateKey);

        // Clear variables with confidential content from memory
        sodium_memzero($oldPlainPassword);
        sodium_memzero($privateKey);
    }

    /**
     * @param User   $user
     * @param string $privateKey
     *
     * @throws \Exception
     */
    public function updateWithPrivateKey(User $user, string $privateKey): void
    {
        // get plain user password to be encrypted
        if (null === $plainPassword = $user->getPlainPassword()) {
            throw new \Exception('plainPassword should not be null');
        }

        $user->setMailCryptPrivateSecret(CryptoSecretHandler::create($privateKey, $plainPassword)->encode());

        // Clear variables with confidential content from memory
        sodium_memzero($plainPassword);
        sodium_memzero($privateKey);

        $this->manager->flush();
    }

    /**
     * @param User   $user
     * @param string $password
     *
     * @return string
     *
     * @throws \Exception
     */
    public function decrypt(User $user, string $password): string
    {
        if (null === $secret = $user->getMailCryptPrivateSecret()) {
            throw new \Exception('secret should not be null');
        }

        if (null === $privateKey = CryptoSecretHandler::decrypt(CryptoSecret::decode($secret), $password)) {
            throw new \Exception('decryption of mailCryptPrivateSecret failed');
        }

        sodium_memzero($password);

        return $privateKey;
    }
}
