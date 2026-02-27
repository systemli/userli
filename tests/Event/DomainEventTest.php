<?php

declare(strict_types=1);

namespace App\Tests\Event;

use App\Entity\Domain;
use App\Event\DomainEvent;
use PHPUnit\Framework\TestCase;

class DomainEventTest extends TestCase
{
    public function testGetDomain(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $event = new DomainEvent($domain);

        self::assertSame($domain, $event->getDomain());
    }

    public function testConstants(): void
    {
        self::assertEquals('domain.created', DomainEvent::CREATED);
        self::assertEquals('domain.deleted', DomainEvent::DELETED);
    }
}
