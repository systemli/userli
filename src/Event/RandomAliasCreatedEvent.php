<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Alias;
use Symfony\Contracts\EventDispatcher\Event;

final class RandomAliasCreatedEvent extends Event
{
    public const NAME = 'alias.random_created';

    public function __construct(private readonly Alias $alias)
    {
    }

    public function getAlias(): Alias
    {
        return $this->alias;
    }
}
