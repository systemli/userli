<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AppPassword;
use App\Entity\User;
use App\Handler\CryptoSecretHandler;
use App\Handler\MailCryptKeyHandler;
use App\Model\CryptoSecret;
use App\Repository\AppPasswordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

final readonly class AppPasswordManager
{
    public function __construct(
        private AppPasswordRepository $repository,
        private EntityManagerInterface $entityManager,
        private MailCryptKeyHandler $mailCryptKeyHandler,
    ) {
    }

    /**
     * Creates an app password and returns its plaintext, which is not stored.
     *
     * @throws Exception
     */
    public function create(User $user, string $name, string $mainPassword): string
    {
        if ($user->hasMailCryptSecretBox()) {
            $privateKey = $this->mailCryptKeyHandler->decrypt($user, $mainPassword);
        } else {
            // Dovecot needs the MailCrypt key on login, so every app password carries a copy
            $this->mailCryptKeyHandler->create($user, $mainPassword);
            $privateKey = (string) $user->getPlainMailCryptPrivateKey();
            $user->eraseCredentials();
        }

        $plain = bin2hex(random_bytes(16));
        $secretBox = CryptoSecretHandler::create($privateKey, $plain);
        sodium_memzero($privateKey);

        $this->entityManager->persist(new AppPassword($user, $name, $this->hash($plain), $secretBox->encode()));
        $this->entityManager->flush();

        return $plain;
    }

    public function find(User $user, string $plain): ?AppPassword
    {
        return $this->repository->findOneBy(['user' => $user, 'hash' => $this->hash($plain)]);
    }

    /**
     * @throws Exception
     */
    public function decryptMailCryptKey(AppPassword $appPassword, string $plain): string
    {
        $privateKey = CryptoSecretHandler::decrypt(CryptoSecret::decode($appPassword->getMailCryptSecretBox()), $plain);
        if (null === $privateKey) {
            throw new Exception(MailCryptKeyHandler::MESSAGE_DECRYPTION_FAILED);
        }

        return $privateKey;
    }

    public function delete(AppPassword $appPassword): void
    {
        $this->entityManager->remove($appPassword);
        $this->entityManager->flush();
    }

    private function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
