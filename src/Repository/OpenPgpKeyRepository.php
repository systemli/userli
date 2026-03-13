<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\OpenPgpKey;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        $qb = $this->createQueryBuilder('k')
            ->select('COUNT(k.id)');

        if ('' !== $search) {
            $qb->andWhere('k.email LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<OpenPgpKey>
     */
    #[Override]
    public function findPaginatedBySearch(string $search, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('k')
            ->orderBy('k.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ('' !== $search) {
            $qb->andWhere('k.email LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        return $qb->getQuery()->getResult();
    }
}
