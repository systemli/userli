<?php

namespace App\Event;

use App\Entity\Alias;
use App\Traits\AliasAwareTrait;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class AliasCreatedEvent
 */
class AliasCreatedEvent extends Event
{
    use AliasAwareTrait;

    const NAME = 'alias.created';

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
