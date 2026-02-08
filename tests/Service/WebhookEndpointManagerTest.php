<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\WebhookEndpoint;
use App\Service\WebhookEndpointManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class WebhookEndpointManagerTest extends TestCase
{
    public function testFindAllDelegatesToRepository(): void
    {
        $endpoints = [new WebhookEndpoint('https://example.org/hook', 'secret1')];

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findBy')->with([], ['id' => 'ASC'])->willReturn($endpoints);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->with(WebhookEndpoint::class)->willReturn($repository);

        $manager = new WebhookEndpointManager($em);

        self::assertSame($endpoints, $manager->findAll());
    }

    public function testCreatePersistsAndFlushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function ($endpoint) {
                return $endpoint instanceof WebhookEndpoint
                    && $endpoint->getUrl() === 'https://example.org/hook'
                    && $endpoint->getSecret() === 'my-secret'
                    && $endpoint->getEvents() === ['user.created']
                    && $endpoint->isEnabled() === true;
            }));
        $em->expects($this->once())->method('flush');

        $manager = new WebhookEndpointManager($em);
        $result = $manager->create('https://example.org/hook', 'my-secret', ['user.created'], true);

        self::assertInstanceOf(WebhookEndpoint::class, $result);
        self::assertSame('https://example.org/hook', $result->getUrl());
    }

    public function testUpdateModifiesEndpointAndFlushes(): void
    {
        $endpoint = new WebhookEndpoint('https://old.org/hook', 'old-secret');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $manager = new WebhookEndpointManager($em);
        $manager->update($endpoint, 'https://new.org/hook', 'new-secret', ['user.deleted'], false);

        self::assertSame('https://new.org/hook', $endpoint->getUrl());
        self::assertSame('new-secret', $endpoint->getSecret());
        self::assertSame(['user.deleted'], $endpoint->getEvents());
        self::assertFalse($endpoint->isEnabled());
    }

    public function testDeleteRemovesEndpointAndFlushes(): void
    {
        $endpoint = new WebhookEndpoint('https://example.org/hook', 'secret');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($endpoint);
        $em->expects($this->once())->method('flush');

        $manager = new WebhookEndpointManager($em);
        $manager->delete($endpoint);
    }
}
