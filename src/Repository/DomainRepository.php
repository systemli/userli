<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Domain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Domain>
 */
final class DomainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Domain::class);
    }

    public function findByName(string $name): ?Domain
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function existsByName(string $name): bool
    {
        return (bool) $this->createQueryBuilder('d')
            ->select('1')
            ->where('d.name = :name')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getDefaultDomain(): ?Domain
    {
        return $this->findOneBy([], ['id' => 'ASC']);
    }

    public function countBySearch(string $search = ''): int
    {
        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)');

        if ('' !== $search) {
            $qb->where('d.name LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Domain[]
     */
    public function findPaginatedBySearch(string $search, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('d')
            ->orderBy('d.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ('' !== $search) {
            $qb->where('d.name LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        return $qb->getQuery()->getResult();
    }
}
