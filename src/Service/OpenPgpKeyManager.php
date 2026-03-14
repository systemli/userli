<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PaginatedResult;
use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgDataException;
use App\Exception\NoGpgKeyForUserException;
use App\Repository\OpenPgpKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Tuupola\Base32;

final readonly class OpenPgpKeyManager
{
    private const int PAGE_SIZE = 20;

    public function __construct(
        private EntityManagerInterface $em,
        private OpenPgpKeyRepository $repository,
        private DomainGuesser $domainGuesser,
        private GpgKeyImporter $gpgKeyImporter,
    ) {
    }

    /**
     * @return PaginatedResult<OpenPgpKey>
     */
    public function findPaginated(int $page = 1, string $search = ''): PaginatedResult
    {
        return PaginatedResult::fromSearchableRepository($this->repository, $page, self::PAGE_SIZE, $search);
    }

    /**
     * Encodes the email address local part according to the WKD Web Key Directory RFC draft.
     * See https://tools.ietf.org/html/draft-koch-openpgp-webkey-service-10 for further information.
     */
    public static function wkdHash(string $localPart): string
    {
        $base32Encoder = new Base32(['characters' => Base32::ZBASE32]);

        return $base32Encoder->encode(sha1(strtolower($localPart), true)); // NOSONAR not used in secure contexts
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
            $openPgpKey->setUploader($user);
        }

        $openPgpKeyNew = $this->gpgKeyImporter->import($email, $key);

        $openPgpKey->setEmail($openPgpKeyNew->getEmail());
        $openPgpKey->setKeyId($openPgpKeyNew->getKeyId());
        $openPgpKey->setKeyFingerprint($openPgpKeyNew->getKeyFingerprint());
        $openPgpKey->setKeyExpireTime($openPgpKeyNew->getKeyExpireTime());
        $openPgpKey->setKeyData($openPgpKeyNew->getKeyData());

        [$localPart] = explode('@', $openPgpKeyNew->getEmail());
        $openPgpKey->setWkdHash(self::wkdHash($localPart));

        $domain = $this->domainGuesser->guess($openPgpKeyNew->getEmail());
        if (null === $domain) {
            throw new RuntimeException(sprintf('No matching domain found for email "%s"', $openPgpKeyNew->getEmail()));
        }

        $openPgpKey->setDomain($domain);

        $this->em->persist($openPgpKey);
        $this->em->flush();

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

        $this->em->remove($openPgpKey);
        $this->em->flush();
    }
}
