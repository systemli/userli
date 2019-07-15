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
    use IdTrait;
    use CreationTimeTrait;
    use UpdatedTimeTrait;
    use NameTrait;

    public function __toString()
    {
        return ($this->getName()) ?: '';
    }
}
