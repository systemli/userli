<?php

namespace App\Event;

use App\Entity\Alias;
use App\Traits\AliasAwareTrait;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author louis <louis@systemli.org>
 */
class AliasEvent extends Event
{
    use AliasAwareTrait;

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
