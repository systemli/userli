<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Announcement;
use App\Entity\Domain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Announcement>
 */
final class AnnouncementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Announcement::class);
    }

    public function countByDomain(?Domain $domain = null): int
    {
        $qb = $this->createQueryBuilder('a')->select('COUNT(a.id)');

        if (null !== $domain) {
            $qb->andWhere('a.domain = :domain')->setParameter('domain', $domain);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<Announcement>
     */
    public function findPaginatedByDomain(?Domain $domain, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if (null !== $domain) {
            $qb->andWhere('a.domain = :domain')->setParameter('domain', $domain);
        }

        return $qb->getQuery()->getResult();
    }
}
