<?php

declare(strict_types=1);

namespace App\Repository;

use DateTimeImmutable;
use App\Entity\UserNotification;
use App\Entity\User;
use App\Enum\UserNotificationType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserNotification>
 */
class UserNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotification::class);
    }

    /**
     * Check if a user received notification of a specific type within specified hours
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

        return (int)$result > 0;
    }

    /**
     * Save a new notification record
     */
    public function save(UserNotification $notification): void
    {
        $this->getEntityManager()->persist($notification);
        $this->getEntityManager()->flush();
    }

    /**
     * Clean up old notifications (older than specified days)
     */
    public function cleanupOldNotifications(int $days = 30, ?string $type = null): int
    {
        $daysAgo = new DateTimeImmutable(sprintf('-%d days', $days));

        $qb = $this->createQueryBuilder('un')
            ->delete()
            ->where('un.creationTime < :daysAgo')
            ->setParameter('daysAgo', $daysAgo);

        if ($type) {
            $qb->andWhere('un.type = :type')
               ->setParameter('type', $type);
        }

        return $qb->getQuery()->execute();
    }
}
