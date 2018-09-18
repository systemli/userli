<?php

namespace AppBundle\Entity;

use AppBundle\Traits\CreationTimeTrait;
use AppBundle\Traits\IdTrait;
use AppBundle\Traits\NameTrait;
use AppBundle\Traits\UpdatedTimeTrait;

/**
 * @author doobry <doobry@systemli.org>
 */
class ReservedName
{
    use IdTrait, CreationTimeTrait, UpdatedTimeTrait, NameTrait;

    public function __toString()
    {
        return ($this->getName()) ?: '';
    }
}
