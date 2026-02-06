<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DomainRepository;
use App\Traits\CreationTimeTrait;
use App\Traits\IdTrait;
use App\Traits\NameTrait;
use App\Traits\UpdatedTimeTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Stringable;

#[ORM\Entity(repositoryClass: DomainRepository::class)]
#[ORM\Table(name: 'virtual_domains')]
class Domain implements UpdatedTimeInterface, Stringable
{
    use CreationTimeTrait;
    use IdTrait;
    use NameTrait;
    use UpdatedTimeTrait;

    public function __construct()
    {
        $currentDateTime = new DateTime();
        $this->creationTime = $currentDateTime;
        $this->updatedTime = $currentDateTime;
    }

    #[Override]
    public function __toString(): string
    {
        return ($this->getName()) ?: '';
    }
}
