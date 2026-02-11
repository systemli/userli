<?php

declare(strict_types=1);

namespace App\Tests\EntityListener;

use App\Entity\Alias;
use App\EntityListener\InvalidateAliasCacheListener;
use App\Message\InvalidateAliasCache;
use Doctrine\ORM\Event\PostPersistEventArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class InvalidateAliasCacheListenerTest extends TestCase
{
    public function testPostPersist(): void
    {
        $source = 'alias@example.org';
        $alias = new Alias();
        $alias->setSource($source);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->with($this->callback(static function (InvalidateAliasCache $message) use ($source): bool {
                return $message->source === $source;
            }))
            ->willReturn(new Envelope(new InvalidateAliasCache($source)));

        $args = $this->createStub(PostPersistEventArgs::class);

        $listener = new InvalidateAliasCacheListener($bus);
        $listener->postPersist($alias, $args);
    }
}
