<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\OpenPgpKey;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Override;

/**
 * @extends ServiceEntityRepository<OpenPgpKey>
 */
final class OpenPgpKeyRepository extends ServiceEntityRepository implements SearchableRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OpenPgpKey::class);
    }

    /**
     * @return OpenPgpKey[]
     */
    public function findByUploader(User $user): array
    {
        return $this->findBy(['uploader' => $user]);
    }

    public function findByEmail(string $email): ?OpenPgpKey
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByWkdHash(string $hash, string $domain): ?OpenPgpKey
    {
        return $this->createQueryBuilder('k')
            ->where('k.wkdHash = :hash')
            ->andWhere('k.email LIKE :domain')
            ->setParameter('hash', $hash)
            ->setParameter('domain', '%@'.strtolower(str_replace('%', '', $domain)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countKeys(): int
    {
        return (int) $this->createQueryBuilder('k')
            ->select('COUNT(k.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    #[Override]
    public function countBySearch(string $search = ''): int
    {
        return $this->countByFilters($search);
    }

    /**
     * @return array<OpenPgpKey>
     */
    #[Override]
    public function findPaginatedBySearch(string $search, int $limit, int $offset): array
    {
        return $this->findPaginatedByFilters($search, null, $limit, $offset);
    }

    public function countByFilters(string $search = '', ?Domain $domain = null): int
    {
        $qb = $this->createQueryBuilder('k')
            ->select('COUNT(k.id)');

        $this->applyFilters($qb, $search, $domain);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<OpenPgpKey>
     */
    public function findPaginatedByFilters(string $search = '', ?Domain $domain = null, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('k')
            ->orderBy('k.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $this->applyFilters($qb, $search, $domain);

        return $qb->getQuery()->getResult();
    }

    private function applyFilters(QueryBuilder $qb, string $search, ?Domain $domain): void
    {
        if ('' !== $search) {
            $qb->andWhere('k.email LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if (null !== $domain) {
            $qb->andWhere('k.domain = :domain')
                ->setParameter('domain', $domain);
        }
    }
}
