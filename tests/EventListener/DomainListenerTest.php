<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\Domain;
use App\Event\DomainEvent;
use App\EventListener\DomainListener;
use App\Message\ClearCache;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\MessageBusInterface;

class DomainListenerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = DomainListener::getSubscribedEvents();

        self::assertArrayHasKey(DomainEvent::DELETED, $events);
        self::assertEquals('onDomainDeleted', $events[DomainEvent::DELETED]);
        self::assertArrayNotHasKey(DomainEvent::CREATED, $events);
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
