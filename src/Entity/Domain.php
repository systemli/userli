<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DomainRepository;
use App\Traits\CreationTimeTrait;
use App\Traits\IdTrait;
use App\Traits\NameTrait;
use App\Traits\UpdatedTimeTrait;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Stringable;

#[ORM\Entity(repositoryClass: DomainRepository::class)]
#[ORM\Table(name: 'domains')]
class Domain implements UpdatedTimeInterface, Stringable
{
    use CreationTimeTrait;
    use IdTrait;
    use NameTrait;
    use UpdatedTimeTrait;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $invitationEnabled = false;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $invitationLimit = 0;

    public function __construct()
    {
        $this->creationTime = new DateTimeImmutable();
    }

    public function isInvitationEnabled(): bool
    {
        return $this->invitationEnabled;
    }

    public function setInvitationEnabled(bool $invitationEnabled): void
    {
        $this->invitationEnabled = $invitationEnabled;
    }

    public function getInvitationLimit(): int
    {
        return $this->invitationLimit;
    }

    public function setInvitationLimit(int $invitationLimit): void
    {
        $this->invitationLimit = $invitationLimit;
    }

    #[Override]
    public function __toString(): string
    {
        return ($this->getName()) ?: '';
    }
}
