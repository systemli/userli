<?php

declare(strict_types=1);

namespace App\Service;

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

    public function create(string $url, string $secret, ?array $events, bool $enabled): WebhookEndpoint
    {
        $endpoint = new WebhookEndpoint($url, $secret);
        $endpoint->setEvents($events);
        $endpoint->setEnabled($enabled);

        $this->em->persist($endpoint);
        $this->em->flush();

        return $endpoint;
    }

    public function update(WebhookEndpoint $endpoint, string $url, string $secret, ?array $events, bool $enabled): void
    {
        $endpoint->setUrl($url);
        $endpoint->setSecret($secret);
        $endpoint->setEvents($events);
        $endpoint->setEnabled($enabled);

        $this->em->flush();
    }

    public function delete(WebhookEndpoint $endpoint): void
    {
        $this->em->remove($endpoint);
        $this->em->flush();
    }
}
