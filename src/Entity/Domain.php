<?php

namespace App\Entity;

use App\Repository\DomainRepository;
use Stringable;
use DateTime;
use App\Traits\CreationTimeTrait;
use App\Traits\IdTrait;
use App\Traits\NameTrait;
use App\Traits\UpdatedTimeTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: DomainRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'virtual_domains')]
#[UniqueEntity('name')]
class Domain implements Stringable
{
    use IdTrait;
    use CreationTimeTrait;
    use UpdatedTimeTrait;
    use NameTrait;

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
