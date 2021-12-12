<?php

namespace App\Entity;

use App\Traits\CreationTimeTrait;
use App\Traits\IdTrait;
use App\Traits\NameTrait;
use App\Traits\UpdatedTimeTrait;

class ReservedName
{
    use IdTrait;
    use CreationTimeTrait;
    use UpdatedTimeTrait;
    use NameTrait;

    /**
     * ReservedName constructor.
     */
    public function __construct()
    {
        $this->creationTime = new \DateTime();
    }

    public function __toString()
    {
        return ($this->getName()) ?: '';
    }
}
