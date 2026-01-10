<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WebhookDelivery;
use App\Entity\WebhookEndpoint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
