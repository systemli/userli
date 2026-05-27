<?php

declare(strict_types=1);

namespace App\Service\Cache;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.cache.invalidation_strategy')]
interface InvalidationStrategy
{
    public function type(): EntityCacheType;

    public function invalidate(string $identifier): void;
}
