<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReservedNameRepository;
use App\Traits\CreationTimeTrait;
use App\Traits\IdTrait;
use App\Traits\NameTrait;
use App\Traits\UpdatedTimeTrait;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Stringable;

#[ORM\Entity(repositoryClass: ReservedNameRepository::class)]
#[ORM\Table(name: 'virtual_reserved_names')]
class ReservedName implements UpdatedTimeInterface, Stringable
{
    use CreationTimeTrait;
    use IdTrait;
    use NameTrait;
    use UpdatedTimeTrait;

    /**
     * ReservedName constructor.
     */
    public function __construct()
    {
        $this->creationTime = new DateTimeImmutable();
    }

    #[Override]
    public function __toString(): string
    {
        return ($this->getName()) ?: '';
    }
}
