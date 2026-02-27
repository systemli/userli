<?php

declare(strict_types=1);

namespace App\Repository;

trait SearchableRepositoryTrait
{
    public function countBySearch(string $search = ''): int
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)');

        if ('' !== $search) {
            $qb->where('e.name LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return object[]
     */
    public function findPaginatedBySearch(string $search, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('e')
            ->orderBy('e.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ('' !== $search) {
            $qb->where('e.name LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        return $qb->getQuery()->getResult();
    }
}
