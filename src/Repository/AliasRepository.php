<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Alias>
 */
final class AliasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alias::class);
    }

    public function findOneBySource(string $email, ?bool $includeDeleted = false): ?Alias
    {
        if ($includeDeleted) {
            return $this->findOneBy(['source' => $email]);
        }

        return $this->findOneBy(['source' => $email, 'deleted' => false]);
    }

    public function countDomainAliases(Domain $domain): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.domain = :domain')
            ->andWhere('a.deleted = :deleted')
            ->setParameter('domain', $domain)
            ->setParameter('deleted', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns the destination addresses for a given alias source.
     *
     * Uses a scalar query to avoid hydrating full Alias entities.
     *
     * @return string[]
     */
    public function findDestinationsBySource(string $source): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.destination')
            ->where('a.source = :source')
            ->andWhere('a.deleted = :deleted')
            ->setParameter('source', $source)
            ->setParameter('deleted', false)
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * Returns the smtp_quota_limits for an alias, or null if the alias does not exist.
     *
     * Uses a scalar query to avoid hydrating the full Alias entity.
     * An existing alias with no custom limits returns an empty array.
     *
     * @return array<string, int>|null
     */
    public function findSmtpQuotaLimitsBySource(string $source): ?array
    {
        $result = $this->createQueryBuilder('a')
            ->select('a.smtpQuotaLimits')
            ->where('a.source = :source')
            ->andWhere('a.deleted = :deleted')
            ->setParameter('source', $source)
            ->setParameter('deleted', false)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($result === null) {
            return null;
        }

        return $result['smtpQuotaLimits'] ?? [];
    }

    public function countByFilters(string $search = '', ?Domain $domain = null, string $deleted = 'active'): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)');

        $this->applyFilters($qb, $search, $domain, $deleted);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Alias[]
     */
    public function findPaginatedByFilters(string $search = '', ?Domain $domain = null, string $deleted = 'active', int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $this->applyFilters($qb, $search, $domain, $deleted);

        return $qb->getQuery()->getResult();
    }

    private function applyFilters(QueryBuilder $qb, string $search, ?Domain $domain, string $deleted): void
    {
        if ('' !== $search) {
            $qb->leftJoin('a.user', 'u')
                ->andWhere('a.source LIKE :search OR a.destination LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if (null !== $domain) {
            $qb->andWhere('a.domain = :domain')
                ->setParameter('domain', $domain);
        }

        if ('active' === $deleted) {
            $qb->andWhere('a.deleted = :deleted')
                ->setParameter('deleted', false);
        } elseif ('deleted' === $deleted) {
            $qb->andWhere('a.deleted = :deleted')
                ->setParameter('deleted', true);
        }
    }

    /**
     * @return Alias[]
     */
    public function findByUser(User $user, ?bool $random = null): array
    {
        $criteria = ['user' => $user, 'deleted' => false];

        if (null !== $random) {
            $criteria['random'] = $random;
        }

        return $this->findBy($criteria);
    }

    /**
     * Find active aliases owned by the user across all domains.
     *
     * Temporarily disables the domain filter to include cross-domain aliases
     * (e.g. aliases where the source domain differs from the user's domain).
     * If the domain filter is not active (e.g. for API-authenticated requests
     * where BeforeRequestListener does not enable it), this behaves identically
     * to findByUser().
     *
     * @return Alias[]
     */
    public function findByUserAcrossDomains(User $user, ?bool $random = null): array
    {
        $filters = $this->getEntityManager()->getFilters();
        $wasEnabled = $filters->isEnabled('domain_filter');

        if ($wasEnabled) {
            $filters->disable('domain_filter');
        }

        try {
            return $this->findByUser($user, $random);
        } finally {
            if ($wasEnabled) {
                $filters->enable('domain_filter');
            }
        }
    }
}
