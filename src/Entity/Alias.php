<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AliasRepository;
use App\Traits\CreationTimeTrait;
use App\Traits\DeleteTrait;
use App\Traits\DomainAwareTrait;
use App\Traits\IdTrait;
use App\Traits\RandomTrait;
use App\Traits\UpdatedTimeTrait;
use App\Traits\UserAwareTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\Index;
use Override;
use Stringable;

#[ORM\Entity(repositoryClass: AliasRepository::class)]
#[ORM\AssociationOverrides([new AssociationOverride(name: 'domain', joinColumns: new ORM\JoinColumn(nullable: true))])]
#[ORM\Table(name: 'virtual_aliases')]
#[Index(columns: ['source', 'deleted'], name: 'source_deleted_idx')]
#[Index(columns: ['destination', 'deleted'], name: 'destination_deleted_idx')]
#[Index(columns: ['user_id', 'deleted'], name: 'user_deleted_idx')]
class Alias implements SoftDeletableInterface, UpdatedTimeInterface, Stringable
{
    use CreationTimeTrait;
    use DeleteTrait;
    use DomainAwareTrait;
    use IdTrait;
    use RandomTrait;
    use UpdatedTimeTrait;
    use UserAwareTrait;

    #[ORM\Column]
    protected ?string $source = null;

    #[ORM\Column(nullable: true)]
    protected ?string $destination = null;

    #[ORM\Column(nullable: true)]
    protected ?string $note = null;

    /**
     * Alias constructor.
     */
    public function __construct()
    {
        $this->deleted = false;
        $this->random = false;
        $currentDateTime = new DateTime();
        $this->creationTime = $currentDateTime;
        $this->updatedTime = $currentDateTime;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): void
    {
        $this->source = $source;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination): void
    {
        $this->destination = $destination;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function clearSensitiveData(): void
    {
        $this->user = null;
        $this->destination = null;
    }

    #[Override]
    public function __toString(): string
    {
        if ($this->source === null) {
            return '';
        }

        if ($this->random) {
            return $this->source.' -> '.$this->destination.' (random)';
        }

        return $this->source.' -> '.$this->destination;
    }
}
