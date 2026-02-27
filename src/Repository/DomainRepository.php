<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Domain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Domain>
 */
final class DomainRepository extends ServiceEntityRepository implements SearchableRepositoryInterface
{
    use SearchableRepositoryTrait;

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
}
