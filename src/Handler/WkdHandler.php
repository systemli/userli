<?php

declare(strict_types=1);

namespace App\Handler;

use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgDataException;
use App\Exception\NoGpgKeyForUserException;
use App\Importer\GpgKeyImporter;
use App\Repository\OpenPgpKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Tuupola\Base32;

/**
 * Manages OpenPGP Web Key Directory (WKD) operations: importing, looking up, and deleting GPG keys.
 *
 * @see GpgKeyImporter for the actual GPG key parsing
 */
final readonly class WkdHandler
{
    private OpenPgpKeyRepository $repository;

    public function __construct(
        private EntityManagerInterface $manager,
    ) {
        $this->repository = $manager->getRepository(OpenPgpKey::class);
    }

    /**
     * Encodes the email address local part according to the WKD Web Key Directory RFC draft.
     * See https://tools.ietf.org/html/draft-koch-openpgp-webkey-service-10 for further information.
     */
    public static function wkdHash(string $localPart): string
    {
        $base32Encoder = new Base32(['characters' => Base32::ZBASE32]);

        return $base32Encoder->encode(sha1(strtolower($localPart), true));
    }

    /**
     * @throws NoGpgDataException
     * @throws NoGpgKeyForUserException
     * @throws MultipleGpgKeysForUserException
     */
    public function importKey(string $key, string $email, ?User $user = null): OpenPgpKey
    {
        if (null === $openPgpKey = $this->repository->findByEmail($email)) {
            $openPgpKey = new OpenPgpKey();
        }

        if (null !== $user) {
            $openPgpKey->setUser($user);
        }

        $openPgpKeyNew = GpgKeyImporter::import($email, $key);

        $openPgpKey->setEmail($openPgpKeyNew->getEmail());
        $openPgpKey->setKeyId($openPgpKeyNew->getKeyId());
        $openPgpKey->setKeyFingerprint($openPgpKeyNew->getKeyFingerprint());
        $openPgpKey->setKeyExpireTime($openPgpKeyNew->getKeyExpireTime());
        $openPgpKey->setKeyData($openPgpKeyNew->getKeyData());

        [$localPart] = explode('@', $openPgpKeyNew->getEmail());
        $openPgpKey->setWkdHash(self::wkdHash($localPart));

        $this->manager->persist($openPgpKey);
        $this->manager->flush();

        return $openPgpKey;
    }

    public function getKey(string $email): ?OpenPgpKey
    {
        return $this->repository->findByEmail($email);
    }

    public function deleteKey(string $email): void
    {
        if (null === $openPgpKey = $this->repository->findByEmail($email)) {
            return;
        }

        $this->manager->remove($openPgpKey);
        $this->manager->flush();
    }
}
