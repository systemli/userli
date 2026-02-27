<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\Domain;
use App\Event\DomainEvent;
use App\EventListener\DomainListener;
use App\Message\ClearCache;
use App\Message\CreatePostmasterAlias;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\MessageBusInterface;

class DomainListenerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = DomainListener::getSubscribedEvents();

        self::assertArrayHasKey(DomainEvent::CREATED, $events);
        self::assertEquals('onDomainCreated', $events[DomainEvent::CREATED]);
        self::assertArrayHasKey(DomainEvent::DELETED, $events);
        self::assertEquals('onDomainDeleted', $events[DomainEvent::DELETED]);
    }

    public function testOnDomainCreatedDispatchesCreatePostmasterAlias(): void
    {
        $domain = $this->createStub(Domain::class);
        $domain->method('getId')->willReturn(42);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function ($message) {
                return $message instanceof CreatePostmasterAlias
                    && $message->domainId === 42;
            }));

        $listener = new DomainListener($bus);
        $listener->onDomainCreated(new DomainEvent($domain));
    }

    public function testOnDomainDeletedDispatchesClearCache(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ClearCache::class));

        $listener = new DomainListener($bus);
        $listener->onDomainDeleted(new DomainEvent($domain));
    }
}
