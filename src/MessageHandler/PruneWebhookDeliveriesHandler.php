<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\WebhookDelivery;
use App\Message\PruneWebhookDeliveries;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PruneWebhookDeliveriesHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(PruneWebhookDeliveries $message): void
    {
        $before = new DateTimeImmutable('-14 days');

        $qb = $this->entityManager->createQueryBuilder()
            ->delete(WebhookDelivery::class, 'd')
            ->where('d.dispatchedTime < :before')
            ->andWhere('d.success = :success')
            ->setParameter('success', true)
            ->setParameter('before', $before);

        $deleted = $qb->getQuery()->execute();

        $this->logger->info('Pruned webhook deliveries', ['deleted' => $deleted]);
    }
}
