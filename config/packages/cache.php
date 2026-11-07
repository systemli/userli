<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'framework' => [
        'cache' => [
            // Per environment, so tests sharing the Redis of the dev setup don't overwrite its cache
            'prefix_seed' => 'userli_%kernel.environment%',
            'app' => ($_ENV['REDIS_URL'] ?? $_SERVER['REDIS_URL'] ?? null)
                ? 'cache.adapter.redis'
                : 'cache.adapter.filesystem',
            'default_redis_provider' => $_ENV['REDIS_URL'] ?? $_SERVER['REDIS_URL'] ?? null,
        ],
    ],
]);
