<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AppPassword;
use App\Entity\Domain;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppPassword>
 */
final class AppPasswordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppPassword::class);
    }

    /**
     * @return AppPassword[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user, 'expiresAt' => null], ['creationTime' => 'DESC']);
    }

    public function findValidByHash(User $user, string $hash): ?AppPassword
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->andWhere('a.hash = :hash')
            ->andWhere('a.expiresAt IS NULL OR a.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('hash', $hash)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('a')
            ->delete()
            ->where('a.expiresAt <= :now')
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    public function deleteByUser(User $user): void
    {
        $this->createQueryBuilder('a')
            ->delete()
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function countUsers(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(DISTINCT a.user)')
            ->where('a.expiresAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByFilters(string $search = '', ?Domain $domain = null): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)');

        $this->applyFilters($qb, $search, $domain);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<AppPassword>
     */
    public function findPaginatedByFilters(string $search = '', ?Domain $domain = null, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('a')
            ->addSelect('u')
            ->orderBy('a.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $this->applyFilters($qb, $search, $domain);

        return $qb->getQuery()->getResult();
    }

    // The inner join on users lets the domain_filter scope domain admins, as app_passwords has no domain column.
    private function applyFilters(QueryBuilder $qb, string $search, ?Domain $domain): void
    {
        $qb->innerJoin('a.user', 'u')
            ->andWhere('a.expiresAt IS NULL');

        if ('' !== $search) {
            $qb->andWhere('u.email LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if (null !== $domain) {
            $qb->andWhere('u.domain = :domain')
                ->setParameter('domain', $domain);
        }
    }
}
