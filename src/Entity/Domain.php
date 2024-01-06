<?php

namespace App\Entity;

use App\Traits\CreationTimeTrait;
use App\Traits\IdTrait;
use App\Traits\NameTrait;
use App\Traits\UpdatedTimeTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DomainRepository")
 * @ORM\Table(name="virtual_domains")
 * @ORM\HasLifecycleCallbacks()
 */
class Domain implements \Stringable
{
    use IdTrait;
    use CreationTimeTrait;
    use UpdatedTimeTrait;
    use NameTrait;

    public function __construct()
    {
        $currentDateTime = new \DateTime();
        $this->creationTime = $currentDateTime;
        $this->updatedTime = $currentDateTime;
    }

    public function __toString(): string
    {
        return ($this->getName()) ?: '';
    }
}
