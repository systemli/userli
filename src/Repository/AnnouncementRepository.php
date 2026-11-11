<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Announcement;
use App\Entity\Domain;
use App\Enum\AnnouncementStatus;
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

    public function incrementSentCount(int $id): void
    {
        $this->increment($id, 'sentCount');
    }

    public function incrementNotSentCount(int $id): void
    {
        $this->increment($id, 'notSentCount');
    }

    /**
     * Marks an announcement as failed, unless all its mails were queued in the meantime.
     */
    public function markFailed(int $id): void
    {
        $this->getEntityManager()->createQuery(sprintf('UPDATE %s a SET a.status = :failed WHERE a.id = :id AND a.status != :done', Announcement::class))
            ->setParameter('failed', AnnouncementStatus::FAILED->value)
            ->setParameter('done', AnnouncementStatus::DONE->value)
            ->setParameter('id', $id)
            ->execute();
    }

    /**
     * Increments a counter in the database, so that concurrent workers don't overwrite each other.
     */
    private function increment(int $id, string $field): void
    {
        $this->getEntityManager()->createQuery(sprintf('UPDATE %s a SET a.%2$s = a.%2$s + 1 WHERE a.id = :id', Announcement::class, $field))
            ->setParameter('id', $id)
            ->execute();
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
        // Fetch-join the author, so that an author hidden by the domain filter
        // (e.g. an admin of another domain) is null instead of a failing proxy
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.author', 'author')
            ->addSelect('author')
            ->orderBy('a.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if (null !== $domain) {
            $qb->andWhere('a.domain = :domain')->setParameter('domain', $domain);
        }

        return $qb->getQuery()->getResult();
    }
}
