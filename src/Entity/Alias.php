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
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\Index;
use Override;
use Stringable;

/**
 * Email alias that forwards mail from a source address to a destination user.
 *
 * Aliases can be manually created by users or randomly generated (see {@see $random}).
 * Soft-deletable: deleted aliases retain their source but clear the destination and user
 * reference via {@see clearSensitiveData()}.
 */
#[ORM\Entity(repositoryClass: AliasRepository::class)]
#[ORM\AssociationOverrides([new AssociationOverride(name: 'domain', joinColumns: new ORM\JoinColumn(nullable: true, onDelete: 'CASCADE'))])]
#[ORM\Table(name: 'aliases')]
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

    /** The alias email address that receives mail (e.g. "alias@example.org"). */
    #[ORM\Column]
    protected ?string $source = null;

    /** The target email address mail is forwarded to. Nullable for soft-deleted aliases. */
    #[ORM\Column(nullable: true)]
    protected ?string $destination = null;

    /** Optional user-provided label for this alias. */
    #[ORM\Column(nullable: true)]
    protected ?string $note = null;

    /** Per-alias SMTP rate limits (keys: per_hour, per_day). Falls back to the user's or global settings when null. */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $smtpQuotaLimits = null;

    /**
     * Alias constructor.
     */
    public function __construct()
    {
        $this->creationTime = new DateTimeImmutable();
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

    public function getSmtpQuotaLimits(): ?array
    {
        return $this->smtpQuotaLimits;
    }

    public function setSmtpQuotaLimits(?array $smtpQuotaLimits): void
    {
        $this->smtpQuotaLimits = $smtpQuotaLimits;
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
