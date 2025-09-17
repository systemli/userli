<?php

namespace App\Entity;

use App\Repository\AliasRepository;
use App\Traits\CreationTimeTrait;
use App\Traits\DeleteTrait;
use App\Traits\DomainAwareTrait;
use App\Traits\IdTrait;
use App\Traits\RandomTrait;
use App\Traits\UpdatedTimeTrait;
use App\Traits\UserAwareTrait;
use App\Validator\EmailAddress;
use App\Validator\EmailLength;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\Index;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AliasRepository::class)]
#[ORM\AssociationOverrides([new AssociationOverride(name: 'domain', joinColumns: new ORM\JoinColumn(nullable: true))])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'virtual_aliases')]
#[Index(name: 'source_deleted_idx', columns: ['source', 'deleted'])]
#[Index(name: 'destination_deleted_idx', columns: ['destination', 'deleted'])]
#[Index(name: 'user_deleted_idx', columns: ['user_id', 'deleted'])]
class Alias implements SoftDeletableInterface, Stringable
{
    use IdTrait;
    use CreationTimeTrait;
    use UpdatedTimeTrait;
    use DeleteTrait;
    use UserAwareTrait;
    use DomainAwareTrait;
    use RandomTrait;

    #[ORM\Column]
    protected ?string $source = null;

    #[ORM\Column(nullable: true)]
    protected ?string $destination = null;

    #[ORM\Column(length: 40, nullable: true)]
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
        if ($note === null) {
            $this->note = null;
            return;
        }

        $note = trim($note);

        // enforce max length of 40 to match database column and validation
        if (mb_strlen($note) > 40) {
            $note = mb_substr($note, 0, 40);
        }

        $this->note = $note;
    }

    public function clearSensitiveData(): void
    {
        $this->user = null;
        $this->destination = null;
    }

    public function __toString(): string
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
