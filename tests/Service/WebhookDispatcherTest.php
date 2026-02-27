<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Domain;
use App\Entity\User;
use App\Entity\WebhookDelivery;
use App\Entity\WebhookEndpoint;
use App\Enum\WebhookEvent;
use App\Message\SendWebhook;
use App\Service\WebhookDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class WebhookDispatcherTest extends TestCase
{
    public function testDispatchUserEventPersistsAndDispatchesForMatchingEndpoints(): void
    {
        $user = new User('user@example.org');

        $endpointAll = new WebhookEndpoint('https://example.test/a', 'secret-a');
        $endpointFilteredMatch = new WebhookEndpoint('https://example.test/b', 'secret-b');
        $endpointFilteredMatch->setEvents(['user.created']);
        $endpointFilteredSkip = new WebhookEndpoint('https://example.test/c', 'secret-c');
        $endpointFilteredSkip->setEvents(['other.event']);

        $repo = $this->createStub(EntityRepository::class);
        $repo->method('getClassName')->willReturn(WebhookEndpoint::class);
        $repo->method('findBy')->with(['enabled' => true])->willReturn([$endpointAll, $endpointFilteredMatch, $endpointFilteredSkip]);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(WebhookEndpoint::class)->willReturn($repo);
        $em->expects($this->exactly(2))->method('persist')->with($this->callback(static function ($entity) use (&$persisted) {
            self::assertInstanceOf(WebhookDelivery::class, $entity);
            $persisted[] = $entity;

            return true;
        }));
        $em->expects($this->exactly(4))->method('flush');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(2))->method('dispatch')->with($this->callback(static function ($message) use (&$persisted) {
            // $message may be an Envelope or the message itself depending on messenger internals; we accept both
            if ($message instanceof Envelope) {
                self::assertInstanceOf(SendWebhook::class, $message->getMessage());
                $inner = $message->getMessage();
            } else {
                self::assertInstanceOf(SendWebhook::class, $message);
                $inner = $message;
            }
            $ids = array_map(static fn (WebhookDelivery $d) => (string) $d->getId(), $persisted);
            self::assertContains($inner->deliveryId, $ids);

            return true;
        }))->willReturnCallback(static function ($message) {
            // Always return an Envelope as messenger expects
            return $message instanceof Envelope ? $message : new Envelope($message);
        });

        $dispatcher = new WebhookDispatcher($em, $bus);
        $dispatcher->dispatchUserEvent($user, WebhookEvent::USER_CREATED);

        self::assertCount(2, $persisted, 'Only endpoints matching the filter (or without filter) should persist deliveries.');
        foreach ($persisted as $delivery) {
            /** @var WebhookDelivery $delivery */
            $headers = $delivery->getRequestHeaders();
            self::assertArrayHasKey('X-Webhook-Signature', $headers);
            // After flush the dispatcher sets X-Webhook-Id
            self::assertArrayHasKey('X-Webhook-Id', $headers, 'X-Webhook-Id header should be added after initial flush.');
        }
    }

    public function testDispatchUserEventSkipsEndpointWithNonMatchingDomain(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $otherDomain = new Domain();
        $otherDomain->setName('other.org');

        $user = new User('user@example.org');
        $user->setDomain($domain);

        // Endpoint filtered to other.org only — should be skipped for user@example.org
        $endpointDomainSkip = new WebhookEndpoint('https://example.test/a', 'secret-a');
        $endpointDomainSkip->addDomain($otherDomain);

        $repo = $this->createStub(EntityRepository::class);
        $repo->method('findBy')->with(['enabled' => true])->willReturn([$endpointDomainSkip]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(WebhookEndpoint::class)->willReturn($repo);
        $em->expects($this->never())->method('persist');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $dispatcher = new WebhookDispatcher($em, $bus);
        $dispatcher->dispatchUserEvent($user, WebhookEvent::USER_CREATED);
    }

    public function testDispatchUserEventDispatchesForMatchingDomain(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $user = new User('user@example.org');
        $user->setDomain($domain);

        // Endpoint filtered to example.org — should dispatch
        $endpointDomainMatch = new WebhookEndpoint('https://example.test/a', 'secret-a');
        $endpointDomainMatch->addDomain($domain);

        $repo = $this->createStub(EntityRepository::class);
        $repo->method('findBy')->with(['enabled' => true])->willReturn([$endpointDomainMatch]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(WebhookEndpoint::class)->willReturn($repo);
        $em->expects($this->once())->method('persist');
        $em->expects($this->exactly(2))->method('flush');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())->method('dispatch')->willReturnCallback(static function ($message) {
            return $message instanceof Envelope ? $message : new Envelope($message);
        });

        $dispatcher = new WebhookDispatcher($em, $bus);
        $dispatcher->dispatchUserEvent($user, WebhookEvent::USER_CREATED);
    }

    public function testDispatchUserEventDispatchesForEndpointWithNoDomainFilter(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $user = new User('user@example.org');
        $user->setDomain($domain);

        // Endpoint with no domains — should dispatch for any domain
        $endpointNoDomains = new WebhookEndpoint('https://example.test/a', 'secret-a');

        $repo = $this->createStub(EntityRepository::class);
        $repo->method('findBy')->with(['enabled' => true])->willReturn([$endpointNoDomains]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(WebhookEndpoint::class)->willReturn($repo);
        $em->expects($this->once())->method('persist');
        $em->expects($this->exactly(2))->method('flush');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())->method('dispatch')->willReturnCallback(static function ($message) {
            return $message instanceof Envelope ? $message : new Envelope($message);
        });

        $dispatcher = new WebhookDispatcher($em, $bus);
        $dispatcher->dispatchUserEvent($user, WebhookEvent::USER_CREATED);
    }
}
