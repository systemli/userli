<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Alias;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    /**
     * @return array|Alias[]
     */
    public function findByUser(User $user, ?bool $random = null, ?bool $disableDomainFilter = false): array
    {
        $filters = $this->getEntityManager()->getFilters();

        if ($filters->isEnabled('domain_filter') && $disableDomainFilter == true) {
            $filters->disable('domain_filter');
        }

        if (isset($random)) {
            return $this->findBy(['user' => $user, 'random' => $random, 'deleted' => false]);
        }

        return $this->findBy(['user' => $user, 'deleted' => false]);
    }
}
