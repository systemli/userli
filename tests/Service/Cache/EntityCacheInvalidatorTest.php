<?php

declare(strict_types=1);

namespace App\Tests\Service\Cache;

use App\Message\InvalidateEntityCache;
use App\Service\Cache\EntityCacheInvalidator;
use App\Service\Cache\EntityCacheType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class EntityCacheInvalidatorTest extends TestCase
{
    public function testDispatchSendsMessageToBus(): void
    {
        $type = EntityCacheType::USER;
        $identifier = 'user@example.org';

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (InvalidateEntityCache $message) use ($type, $identifier): bool {
                return $message->type === $type && $message->identifier === $identifier;
            }))
            ->willReturn(new Envelope(new InvalidateEntityCache($type, $identifier)));

        $invalidator = new EntityCacheInvalidator($bus);
        $invalidator->dispatch($type, $identifier);
    }

    public function testDispatchCoversAllEntityCacheTypes(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::exactly(\count(EntityCacheType::cases())))
            ->method('dispatch')
            ->willReturnCallback(static fn (InvalidateEntityCache $message): Envelope => new Envelope($message));

        $invalidator = new EntityCacheInvalidator($bus);
        foreach (EntityCacheType::cases() as $type) {
            $invalidator->dispatch($type, 'id');
        }
    }
}
