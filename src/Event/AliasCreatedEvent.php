<?php

namespace App\Event;

use App\Entity\Alias;
use App\Traits\AliasAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class AliasCreatedEvent.
 */
class AliasCreatedEvent extends Event
{
    use AliasAwareTrait;

    public const NAME = 'alias.custom_created';

    /**
     * Constructor.
     */
    public function __construct(Alias $alias)
    {
        $this->alias = $alias;
    }
}
