<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PaginatedResult;
use App\Entity\Domain;
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
        private GpgKeyParser $gpgKeyParser,
    ) {
    }

    /**
     * @return PaginatedResult<OpenPgpKey>
     */
    public function findPaginated(int $page = 1, string $search = '', ?Domain $domain = null): PaginatedResult
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $total = $this->repository->countByFilters($search, $domain);
        $totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $items = $this->repository->findPaginatedByFilters($search, $domain, self::PAGE_SIZE, $offset);

        return new PaginatedResult($items, $page, $totalPages, $total);
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

        $result = $this->gpgKeyParser->parse($email, $key);

        $openPgpKey->setEmail($result->email);
        $openPgpKey->setKeyId($result->keyId);
        $openPgpKey->setKeyFingerprint($result->fingerprint);
        $openPgpKey->setKeyExpireTime($result->expireTime);
        $openPgpKey->setKeyData($result->keyData);

        [$localPart] = explode('@', $result->email);
        $openPgpKey->setWkdHash(self::wkdHash($localPart));

        $domain = $this->domainGuesser->guess($result->email);
        if (null === $domain) {
            throw new RuntimeException(sprintf('No matching domain found for email "%s"', $result->email));
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
