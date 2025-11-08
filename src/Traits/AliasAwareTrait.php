<?php

declare(strict_types=1);

namespace App\Traits;

use App\Entity\Alias;

trait AliasAwareTrait
{
    private ?Alias $alias = null;

    public function getAlias(): ?Alias
    {
        return $this->alias;
    }

    public function setAlias(Alias $alias): void
    {
        $this->alias = $alias;
    }
}
