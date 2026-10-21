<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\MailAppPassword;
use App\Entity\User;
use App\Exception\DecryptionFailedException;
use App\Handler\CryptoSecretHandler;
use App\Handler\MailCryptKeyHandler;
use App\Model\CryptoSecret;
use App\Repository\MailAppPasswordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Random\Randomizer;

final readonly class MailAppPasswordManager
{
    // 6 groups of 4 (137 bits), so they can't be mistaken for Nextcloud mail app passwords (5 groups of 5)
    private const string CHARS = 'abcdefgijkmnopqrstwxyzABCDEFGHJKLMNPQRSTWXYZ23456789';

    public function __construct(
        private MailAppPasswordRepository $repository,
        private EntityManagerInterface $entityManager,
        private MailCryptKeyHandler $mailCryptKeyHandler,
    ) {
    }

    /**
     * Creates a mail app password and returns its plaintext, which is not stored.
     *
     * @throws Exception
     */
    public function create(User $user, string $name, string $mainPassword): string
    {
        if ($user->hasMailCryptSecretBox()) {
            $privateKey = $this->mailCryptKeyHandler->decrypt($user, $mainPassword);
        } else {
            // Dovecot needs the MailCrypt key on login, so every mail app password carries a copy
            $this->mailCryptKeyHandler->create($user, $mainPassword);
            $privateKey = (string) $user->getPlainMailCryptPrivateKey();
            $user->eraseCredentials();
        }

        $randomizer = new Randomizer();
        $plain = implode('-', array_map(static fn (): string => $randomizer->getBytesFromString(self::CHARS, 4), range(1, 6)));
        $secretBox = CryptoSecretHandler::create($privateKey, $plain);
        sodium_memzero($privateKey);

        $this->entityManager->persist(new MailAppPassword($user, $name, $this->hash($plain), $secretBox->encode()));
        $this->entityManager->flush();

        return $plain;
    }

    public function find(User $user, string $plain): ?MailAppPassword
    {
        return $this->repository->findOneBy(['user' => $user, 'hash' => $this->hash($plain)]);
    }

    /**
     * @throws DecryptionFailedException
     */
    public function decryptMailCryptKey(MailAppPassword $mailAppPassword, string $plain): string
    {
        $privateKey = CryptoSecretHandler::decrypt(CryptoSecret::decode($mailAppPassword->getMailCryptSecretBox()), $plain);
        if (null === $privateKey) {
            throw new DecryptionFailedException(MailCryptKeyHandler::MESSAGE_DECRYPTION_FAILED);
        }

        return $privateKey;
    }

    public function delete(MailAppPassword $mailAppPassword): void
    {
        $this->entityManager->remove($mailAppPassword);
        $this->entityManager->flush();
    }

    private function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
