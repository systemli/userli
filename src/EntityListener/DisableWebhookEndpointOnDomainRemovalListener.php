<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\Domain;
use App\Entity\WebhookEndpoint;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: Domain::class)]
final readonly class DisableWebhookEndpointOnDomainRemovalListener
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function preRemove(Domain $domain): void
    {
        $endpoints = $this->em->getRepository(WebhookEndpoint::class)
            ->createQueryBuilder('e')
            ->innerJoin('e.domains', 'd')
            ->where('d = :domain')
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getResult();

        foreach ($endpoints as $endpoint) {
            // If this domain is the only one assigned, disable the endpoint
            if ($endpoint->getDomains()->count() === 1) {
                $endpoint->setEnabled(false);
            }
        }
    }
}
