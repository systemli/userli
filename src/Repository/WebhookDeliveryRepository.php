<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WebhookDelivery;
use App\Entity\WebhookEndpoint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WebhookDelivery>
 */
final class WebhookDeliveryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebhookDelivery::class);
    }

    public function countByEndpoint(WebhookEndpoint $endpoint): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.endpoint = :endpoint')
            ->setParameter('endpoint', $endpoint)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByEndpointAndStatus(WebhookEndpoint $endpoint, string $status, string $eventType = ''): int
    {
        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.endpoint = :endpoint')
            ->setParameter('endpoint', $endpoint);

        $this->applyStatusFilter($qb, $status);
        $this->applyEventTypeFilter($qb, $eventType);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return WebhookDelivery[]
     */
    public function findByEndpointAndStatus(
        WebhookEndpoint $endpoint,
        string $status,
        int $limit,
        int $offset,
        string $eventType = '',
    ): array {
        $qb = $this->createQueryBuilder('d')
            ->where('d.endpoint = :endpoint')
            ->setParameter('endpoint', $endpoint)
            ->orderBy('d.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $this->applyStatusFilter($qb, $status);
        $this->applyEventTypeFilter($qb, $eventType);

        return $qb->getQuery()->getResult();
    }

    private function applyStatusFilter(QueryBuilder $qb, string $status): void
    {
        match ($status) {
            'success' => $qb->andWhere('d.success = true'),
            'failed' => $qb->andWhere('d.error IS NOT NULL')->andWhere('d.success = false'),
            'pending' => $qb->andWhere('d.error IS NULL')->andWhere('d.success = false'),
            default => null,
        };
    }

    private function applyEventTypeFilter(QueryBuilder $qb, string $eventType): void
    {
        if ('' !== $eventType) {
            $qb->andWhere('d.type = :eventType')
                ->setParameter('eventType', $eventType);
        }
    }
}
