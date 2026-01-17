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
}
