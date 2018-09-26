<?php

namespace App\Entity;

use App\Traits\CreationTimeTrait;
use App\Traits\IdTrait;
use App\Traits\NameTrait;
use App\Traits\UpdatedTimeTrait;

/**
 * @author louis <louis@systemli.org>
 */
class Domain
{
    use IdTrait, CreationTimeTrait, UpdatedTimeTrait, NameTrait;

    public function __toString()
    {
        return ($this->getName()) ?: '';
    }
}
