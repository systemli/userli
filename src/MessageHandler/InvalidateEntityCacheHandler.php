<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\InvalidateEntityCache;
use App\Service\Cache\InvalidationStrategy;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(sign: true)]
final readonly class InvalidateEntityCacheHandler
{
    /**
     * @param iterable<InvalidationStrategy> $strategies
     */
    public function __construct(
        #[AutowireIterator('app.cache.invalidation_strategy')]
        private iterable $strategies,
    ) {
    }

    public function __invoke(InvalidateEntityCache $message): void
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->type() === $message->type) {
                $strategy->invalidate($message->identifier);

                return;
            }
        }
    }
}
