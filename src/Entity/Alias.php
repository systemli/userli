<?php

namespace App\Entity;

use App\Traits\CreationTimeTrait;
use App\Traits\DeleteTrait;
use App\Traits\DomainAwareTrait;
use App\Traits\IdTrait;
use App\Traits\RandomTrait;
use App\Traits\UpdatedTimeTrait;
use App\Traits\UserAwareTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AliasRepository")
 * @ORM\Table(name="virtual_aliases", indexes={
 *     @Index(name="source_deleted_idx", columns={"source", "deleted"}),
 *     @Index(name="destination_deleted_idx", columns={"destination", "deleted"}),
 *     @Index(name="user_deleted_idx", columns={"user_id", "deleted"})
 * })
 * @ORM\AssociationOverrides({
 *     @AssociationOverride(name="domain", joinColumns=@ORM\JoinColumn(
 *         nullable=true
 *     ))
 * })
 * @ORM\HasLifecycleCallbacks()
 */
class Alias implements SoftDeletableInterface
{
    use IdTrait;
    use CreationTimeTrait;
    use UpdatedTimeTrait;
    use DeleteTrait;
    use UserAwareTrait;
    use DomainAwareTrait;
    use RandomTrait;

    /** @ORM\Column() */
    protected ?string $source = null;

    /** @ORM\Column(nullable=true) */
    protected ?string $destination = null;

    /**
     * Alias constructor.
     */
    public function __construct()
    {
        $this->deleted = false;
        $this->random = false;
        $currentDateTime = new \DateTime();
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

    public function clearSensitiveData(): void
    {
        $this->user = null;
        $this->destination = null;
    }

    public function __toString()
    {
        if ($this->source === null) {
            return '';
        }

        if ($this->random) {
            return $this->source . ' -> ' . $this->destination . ' (random)';
        }

        return $this->source . ' -> ' . $this->destination;
    }
}
