<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Domain;
use App\Entity\WebhookEndpoint;
use Doctrine\ORM\EntityManagerInterface;

final readonly class WebhookEndpointManager
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /** @return WebhookEndpoint[] */
    public function findAll(): array
    {
        return $this->em->getRepository(WebhookEndpoint::class)->findBy([], ['id' => 'ASC']);
    }

    /** @param Domain[] $domains */
    public function create(string $url, string $secret, ?array $events, bool $enabled, array $domains = []): WebhookEndpoint
    {
        $endpoint = new WebhookEndpoint($url, $secret);
        $endpoint->setEvents($events);
        $endpoint->setEnabled($enabled);

        foreach ($domains as $domain) {
            $endpoint->addDomain($domain);
        }

        $this->em->persist($endpoint);
        $this->em->flush();

        return $endpoint;
    }

    /** @param Domain[] $domains */
    public function update(WebhookEndpoint $endpoint, string $url, string $secret, ?array $events, bool $enabled, array $domains = []): void
    {
        $endpoint->setUrl($url);
        $endpoint->setSecret($secret);
        $endpoint->setEvents($events);
        $endpoint->setEnabled($enabled);

        // Sync domains: remove old, add new
        foreach ($endpoint->getDomains()->toArray() as $domain) {
            $endpoint->removeDomain($domain);
        }

        foreach ($domains as $domain) {
            $endpoint->addDomain($domain);
        }

        $this->em->flush();
    }

    public function delete(WebhookEndpoint $endpoint): void
    {
        $this->em->remove($endpoint);
        $this->em->flush();
    }
}
