<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\WebhookDelivery;
use App\Entity\WebhookEndpoint;
use App\Enum\WebhookEvent;
use App\Message\SendWebhook;
use App\Service\WebhookDispatcher;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Envelope;
use Doctrine\ORM\EntityManagerInterface;

class WebhookDispatcherTest extends TestCase
{
    public function testDispatchUserEventPersistsAndDispatchesForMatchingEndpoints(): void
    {
        $user = new User();
        $user->setEmail('user@example.org');

        $endpointAll = new WebhookEndpoint('https://example.test/a', 'secret-a');
        $endpointFilteredMatch = new WebhookEndpoint('https://example.test/b', 'secret-b');
        $endpointFilteredMatch->setEvents(['user.created']);
        $endpointFilteredSkip = new WebhookEndpoint('https://example.test/c', 'secret-c');
        $endpointFilteredSkip->setEvents(['other.event']);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->method('getClassName')->willReturn(WebhookEndpoint::class);
        $repo->method('findBy')->with(['enabled' => true])->willReturn([$endpointAll, $endpointFilteredMatch, $endpointFilteredSkip]);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(WebhookEndpoint::class)->willReturn($repo);
        $em->expects($this->exactly(2))->method('persist')->with($this->callback(function ($entity) use (&$persisted) {
            $this->assertInstanceOf(WebhookDelivery::class, $entity);
            $persisted[] = $entity;
            return true;
        }));
        $em->expects($this->exactly(4))->method('flush');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(2))->method('dispatch')->with($this->callback(function ($message) use (&$persisted) {
            // $message may be an Envelope or the message itself depending on messenger internals; we accept both
            if ($message instanceof Envelope) {
                $this->assertInstanceOf(SendWebhook::class, $message->getMessage());
                $inner = $message->getMessage();
            } else {
                $this->assertInstanceOf(SendWebhook::class, $message);
                $inner = $message;
            }
            $ids = array_map(fn(WebhookDelivery $d) => (string)$d->getId(), $persisted);
            $this->assertContains($inner->deliveryId, $ids);
            return true;
        }))->willReturnCallback(function ($message) {
            // Always return an Envelope as messenger expects
            return $message instanceof Envelope ? $message : new Envelope($message);
        });

        $dispatcher = new WebhookDispatcher($em, $bus);
        $dispatcher->dispatchUserEvent($user, WebhookEvent::USER_CREATED);

        $this->assertCount(2, $persisted, 'Only endpoints matching the filter (or without filter) should persist deliveries.');
        foreach ($persisted as $delivery) {
            /** @var WebhookDelivery $delivery */
            $headers = $delivery->getRequestHeaders();
            $this->assertArrayHasKey('X-Webhook-Signature', $headers);
            // After flush the dispatcher sets X-Webhook-Id
            $this->assertArrayHasKey('X-Webhook-Id', $headers, 'X-Webhook-Id header should be added after initial flush.');
        }
    }
}
