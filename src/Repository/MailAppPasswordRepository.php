<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MailAppPassword;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MailAppPassword>
 */
final class MailAppPasswordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailAppPassword::class);
    }

    /**
     * @return MailAppPassword[]
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
