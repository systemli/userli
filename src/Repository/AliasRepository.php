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
