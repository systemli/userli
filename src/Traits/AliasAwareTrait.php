<?php

namespace App\Traits;

use App\Entity\Alias;

/**
 * @author doobry <doobry@systemli.org>
 */
trait AliasAwareTrait
{
    /**
     * @var Alias|nulll
     */
    private $alias;

    /**
     * @return Alias|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param Alias $alias
     */
    public function setAlias(Alias $alias)
    {
        $this->alias = $alias;
    }
}
