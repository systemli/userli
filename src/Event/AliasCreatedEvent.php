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

    public const CUSTOM = 'alias.custom_created';
    public const RANDOM = 'alias.random_created';

    public function __construct(Alias $alias)
    {
        $this->alias = $alias;
    }
}
