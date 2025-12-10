<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Alias;
use App\Traits\AliasAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class RandomAliasCreatedEvent.
 */
final class RandomAliasCreatedEvent extends Event
{
    use AliasAwareTrait;

    public const NAME = 'alias.random_created';

    /**
     * Constructor.
     */
    public function __construct(Alias $alias)
    {
        $this->alias = $alias;
    }
}
