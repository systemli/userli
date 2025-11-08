<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\UserNotification;
use App\Message\PruneUserNotifications;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class PruneUserNotificationsHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(PruneUserNotifications $message): void
    {
        $before = new DateTimeImmutable('-30 days');

        $qb = $this->entityManager->createQueryBuilder()
            ->delete(UserNotification::class, 'n')
            ->where('n.creationTime < :before')
            ->setParameter('before', $before);

        $deleted = $qb->getQuery()->execute();

        $this->logger->info('Pruned user notifications', ['deleted' => $deleted]);
    }
}
