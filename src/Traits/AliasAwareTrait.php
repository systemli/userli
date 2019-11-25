<?php

namespace App\Traits;

use App\Entity\Alias;

trait AliasAwareTrait
{
    /**
     * @var Alias|null
     */
    private $alias;

    /**
     * @return Alias|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    public function setAlias(Alias $alias)
    {
        $this->alias = $alias;
    }
}
