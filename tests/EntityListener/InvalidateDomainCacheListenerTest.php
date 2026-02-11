<?php

declare(strict_types=1);

namespace App\Tests\EntityListener;

use App\Entity\Domain;
use App\EntityListener\InvalidateDomainCacheListener;
use App\Message\InvalidateDomainCache;
use Doctrine\ORM\Event\PostPersistEventArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class InvalidateDomainCacheListenerTest extends TestCase
{
    public function testPostPersist(): void
    {
        $name = 'example.org';
        $domain = new Domain();
        $domain->setName($name);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->with($this->callback(static function (InvalidateDomainCache $message) use ($name): bool {
                return $message->name === $name;
            }))
            ->willReturn(new Envelope(new InvalidateDomainCache($name)));

        $args = $this->createStub(PostPersistEventArgs::class);

        $listener = new InvalidateDomainCacheListener($bus);
        $listener->postPersist($domain, $args);
    }
}
