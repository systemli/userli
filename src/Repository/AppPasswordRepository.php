<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AppPassword;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppPassword>
 */
final class AppPasswordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppPassword::class);
    }

    /**
     * @return AppPassword[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['creationTime' => 'DESC']);
    }

    public function deleteByUser(User $user): void
    {
        $this->createQueryBuilder('a')
            ->delete()
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function countUsers(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(DISTINCT a.user)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
