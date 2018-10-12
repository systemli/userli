<?php

namespace App\Event;

use App\Entity\Alias;
use App\Traits\AliasAwareTrait;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class RandomAliasCreatedEvent
 */
class RandomAliasCreatedEvent extends Event
{
    use AliasAwareTrait;

    const NAME = 'alias.random_created';

    /**
     * Constructor.
     *
     * @param Alias $alias
     */
    public function __construct(Alias $alias)
    {
        $this->alias = $alias;
    }
}
