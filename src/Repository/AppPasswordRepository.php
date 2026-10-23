<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AppPassword;
use App\Entity\Domain;
use App\Entity\User;
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
        return $this->findBy(['user' => $user], ['creationTime' => 'DESC']);
    }

    /**
     * Schedules the removal of all app passwords of the user, so it is flushed
     * together with the other changes to the user.
     */
    public function removeByUser(User $user): void
    {
        foreach ($this->findBy(['user' => $user]) as $appPassword) {
            $this->getEntityManager()->remove($appPassword);
        }
    }

    public function countUsers(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(DISTINCT a.user)')
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
        $qb->innerJoin('a.user', 'u');

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
