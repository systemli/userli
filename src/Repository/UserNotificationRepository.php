<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserNotification;
use App\Enum\UserNotificationType;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserNotification>
 */
final class UserNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotification::class);
    }

    /**
     * @return array<UserNotification>
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    /**
     * Check if a user received notification of a specific type within specified hours.
     */
    public function hasRecentNotification(User $user, UserNotificationType $type, int $hours = 24): bool
    {
        $hoursAgo = new DateTimeImmutable(sprintf('-%d hours', $hours));

        $result = $this->createQueryBuilder('un')
            ->select('COUNT(un.id)')
            ->where('un.user = :user')
            ->andWhere('un.type = :type')
            ->andWhere('un.creationTime > :hoursAgo')
            ->setParameter('user', $user)
            ->setParameter('type', $type->value)
            ->setParameter('hoursAgo', $hoursAgo)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result > 0;
    }

    public function emailHasExpiryNotification(string $email, UserNotificationType $type) :bool
    {
        $result = $this->createQueryBuilder('un')
            ->select('COUNT(un.id)')
            ->where('un.type = :type')
            ->andWhere('un.metadata.email = :email')
            ->setParameter('type', $type->value)
            ->setParameter('email', $email)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result > 0;
    }


    /**
     * Save a new notification record.
     */
    public function save(UserNotification $notification): void
    {
        $this->getEntityManager()->persist($notification);
        $this->getEntityManager()->flush();
    }

    public function deleteByUserAndType(User $user, UserNotificationType $type): void
    {
        $this->createQueryBuilder('un')
            ->delete()
            ->where('un.user = :user')
            ->andWhere('un.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type->value)
            ->getQuery()
            ->execute();
    }

    public function deleteByIds(array $ids): void
    {
        $this->createQueryBuilder('n')
            ->delete()
            ->where('n.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }

    public function findNotificationsByUsersAndType(array $users, UserNotificationType $type) {
        if ($users === []) {
            return [];
        }

        $qb = $this->createQueryBuilder('un')
            ->where('un.user IN (:users)')
            ->andWhere('un.type = :type')
            ->setParameter('users', $users)
            ->setParameter('type', $type->value);
            
            return (array) $qb->getQuery()->getResult();
            
    }

    public function countByFilters(string $email = '', string $type = ''): int
    {
        $qb = $this->createQueryBuilder('un')
            ->select('COUNT(un.id)')
            ->join('un.user', 'u');

        if ('' !== $email) {
            $qb->andWhere('u.email LIKE :email')
                ->setParameter('email', '%'.$email.'%');
        }

        if ('' !== $type) {
            $qb->andWhere('un.type = :type')
                ->setParameter('type', $type);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<UserNotification>
     */
    public function findPaginatedByFilters(string $email, string $type, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('un')
            ->join('un.user', 'u')
            ->orderBy('un.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ('' !== $email) {
            $qb->andWhere('u.email LIKE :email')
                ->setParameter('email', '%'.$email.'%');
        }

        if ('' !== $type) {
            $qb->andWhere('un.type = :type')
                ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }
}
