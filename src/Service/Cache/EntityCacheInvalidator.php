<?php

declare(strict_types=1);

namespace App\Service\Cache;

use App\Message\InvalidateEntityCache;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class EntityCacheInvalidator
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    public function dispatch(EntityCacheType $type, string $identifier): void
    {
        $this->bus->dispatch(new InvalidateEntityCache($type, $identifier));
    }
}
