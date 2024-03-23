<?php

namespace App\Handler;

use Exception;
use App\Entity\User;
use App\Model\CryptoSecret;
use App\Model\MailCryptKeyPair;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * Class AliasHandler.
 */
class MailCryptKeyHandler
{
    // Use elliptic curve type 'secp521r1' for MailCrypt keys
    private const MAIL_CRYPT_PRIVATE_KEY_TYPE = OPENSSL_KEYTYPE_EC;
    private const MAIL_CRYPT_CURVE_NAME = 'secp521r1';

    /**
     * MailCryptPrivateKeyHandler constructor.
     */
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    /**
     * @throws Exception
     */
    public function toPkcs8(string $privateKey): string
    {
        // Unfortunately, there doesn't seem to be a way to transform elliptic curve
        // keys from traditional PEM to PKCS#8 format within PHP yet. The OpenSSL
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
            throw new Exception('Transforming key to PKCS#8 with OpenSSL failed. OpenSSL exited unsuccessfully: '.$process->getErrorOutput());
        }
        if (!str_starts_with($process->getOutput(), '-----BEGIN PRIVATE KEY-----')) {
            throw new Exception('Transforming key to PKCS#8 with OpenSSL failed. OpenSSL output is no valid PKCS#8 key: '.$process->getOutput());
        }

        return $process->getOutput();
    }

    /**
     * @throws Exception
     */
    public function create(User $user): void
    {
        $pKey = openssl_pkey_new([
            'private_key_type' => self::MAIL_CRYPT_PRIVATE_KEY_TYPE,
            'curve_name' => self::MAIL_CRYPT_CURVE_NAME,
        ]);
        openssl_pkey_export($pKey, $privateKey);
        $privateKey = base64_encode($this->toPkcs8($privateKey));
        $keyPair = new MailCryptKeyPair($privateKey, base64_encode((string) openssl_pkey_get_details($pKey)['key']));
        sodium_memzero($privateKey);

        // get plain user password
        if (null === $plainPassword = $user->getPlainPassword()) {
            throw new Exception('plainPassword should not be null');
        }

        $mailCryptSecretBox = CryptoSecretHandler::create($keyPair->getPrivateKey(), $plainPassword);
        $user->setMailCryptPublicKey($keyPair->getPublicKey());
        $user->setMailCryptSecretBox($mailCryptSecretBox->encode());
        $user->setPlainMailCryptPrivateKey($keyPair->getPrivateKey());

        // Clear variables with confidential content from memory
        $keyPair->erase();
        sodium_memzero($plainPassword);

        $this->manager->flush();
    }

    /**
     * @throws Exception
     */
    public function update(User $user, string $oldPlainPassword): void
    {
        if (null === $secret = $user->getMailCryptSecretBox()) {
            throw new Exception('secret should not be null');
        }

        if (null === $privateKey = CryptoSecretHandler::decrypt(CryptoSecret::decode($secret), $oldPlainPassword)) {
            throw new Exception('decryption of mailCryptSecretBox failed');
        }

        $this->updateWithPrivateKey($user, $privateKey);

        // Clear variables with confidential content from memory
        sodium_memzero($oldPlainPassword);
        sodium_memzero($privateKey);
    }

    /**
     * @throws Exception
     */
    public function updateWithPrivateKey(User $user, string $privateKey): void
    {
        // get plain user password to be encrypted
        if (null === $plainPassword = $user->getPlainPassword()) {
            throw new Exception('plainPassword should not be null');
        }

        $user->setMailCryptSecretBox(CryptoSecretHandler::create($privateKey, $plainPassword)->encode());

        // Clear variables with confidential content from memory
        sodium_memzero($plainPassword);
        sodium_memzero($privateKey);

        $this->manager->flush();
    }

    /**
     * @throws Exception
     */
    public function decrypt(User $user, string $password): string
    {
        if (null === $secret = $user->getMailCryptSecretBox()) {
            throw new Exception('secret should not be null');
        }

        if (null === $privateKey = CryptoSecretHandler::decrypt(CryptoSecret::decode($secret), $password)) {
            throw new Exception('decryption of mailCryptSecretBox failed');
        }

        sodium_memzero($password);

        return $privateKey;
    }
}
