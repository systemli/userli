<?php

declare(strict_types=1);

namespace App\Tests\Service;

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
}
