<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Domain;
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
        $domain = new Domain();
        $domain->setName('example.org');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function ($endpoint) use ($domain) {
                return $endpoint instanceof WebhookEndpoint
                    && $endpoint->getUrl() === 'https://example.org/hook'
                    && $endpoint->getSecret() === 'my-secret'
                    && $endpoint->getEvents() === ['user.created']
                    && $endpoint->isEnabled() === true
                    && $endpoint->getDomains()->contains($domain);
            }));
        $em->expects($this->once())->method('flush');

        $manager = new WebhookEndpointManager($em);
        $result = $manager->create('https://example.org/hook', 'my-secret', ['user.created'], true, [$domain]);

        self::assertInstanceOf(WebhookEndpoint::class, $result);
        self::assertSame('https://example.org/hook', $result->getUrl());
        self::assertCount(1, $result->getDomains());
    }

    public function testCreateWithoutDomains(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        $manager = new WebhookEndpointManager($em);
        $result = $manager->create('https://example.org/hook', 'my-secret', ['user.created'], true);

        self::assertTrue($result->getDomains()->isEmpty());
    }

    public function testUpdateModifiesEndpointAndFlushes(): void
    {
        $oldDomain = new Domain();
        $oldDomain->setName('old.org');
        $newDomain = new Domain();
        $newDomain->setName('new.org');

        $endpoint = new WebhookEndpoint('https://old.org/hook', 'old-secret');
        $endpoint->addDomain($oldDomain);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $manager = new WebhookEndpointManager($em);
        $manager->update($endpoint, 'https://new.org/hook', 'new-secret', ['user.deleted'], false, [$newDomain]);

        self::assertSame('https://new.org/hook', $endpoint->getUrl());
        self::assertSame('new-secret', $endpoint->getSecret());
        self::assertSame(['user.deleted'], $endpoint->getEvents());
        self::assertFalse($endpoint->isEnabled());
        self::assertCount(1, $endpoint->getDomains());
        self::assertTrue($endpoint->getDomains()->contains($newDomain));
        self::assertFalse($endpoint->getDomains()->contains($oldDomain));
    }

    public function testUpdateClearsDomains(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $endpoint = new WebhookEndpoint('https://example.org/hook', 'secret');
        $endpoint->addDomain($domain);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $manager = new WebhookEndpointManager($em);
        $manager->update($endpoint, 'https://example.org/hook', 'secret', null, true);

        self::assertTrue($endpoint->getDomains()->isEmpty());
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
