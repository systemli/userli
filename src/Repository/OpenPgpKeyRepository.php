<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\OpenPgpKey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OpenPgpKey>
 */
final class OpenPgpKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OpenPgpKey::class);
    }

    public function findByEmail(string $email): ?OpenPgpKey
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByWkdHash(string $hash, string $domain): ?OpenPgpKey
    {
        return $this->createQueryBuilder('k')
            ->where('k.wkdHash = :hash')
            ->andWhere('k.email LIKE :domain')
            ->setParameter('hash', $hash)
            ->setParameter('domain', '%@'.strtolower(str_replace('%', '', $domain)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countKeys(): int
    {
        return $this->count([]);
    }
}
