<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ReservedName;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReservedName>
 */
final class ReservedNameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservedName::class);
    }

    public function findByName(string $name): ?ReservedName
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function countBySearch(string $search = ''): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)');

        if ('' !== $search) {
            $qb->where('r.name LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return ReservedName[]
     */
    public function findPaginatedBySearch(string $search, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ('' !== $search) {
            $qb->where('r.name LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        return $qb->getQuery()->getResult();
    }
}
