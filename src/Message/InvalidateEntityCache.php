<?php

declare(strict_types=1);

namespace App\Message;

use App\Service\Cache\EntityCacheType;
use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final readonly class InvalidateEntityCache
{
    public function __construct(
        public EntityCacheType $type,
        public string $identifier,
    ) {
    }
}
