<?php

namespace App\Entity;

use Stringable;
use DateTime;
use App\Traits\CreationTimeTrait;
use App\Traits\IdTrait;
use App\Traits\NameTrait;
use App\Traits\UpdatedTimeTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ReservedNameRepository")
 * @ORM\Table(name="virtual_reserved_names")
 */
class ReservedName implements Stringable
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
        $currentDateTime = new DateTime();
        $this->creationTime = $currentDateTime;
        $this->updatedTime = $currentDateTime;
    }

    public function __toString(): string
    {
        return ($this->getName()) ?: '';
    }
}
