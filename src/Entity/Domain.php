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

    #[ORM\Embedded(class: InvitationSettings::class, columnPrefix: 'invitation_')]
    private InvitationSettings $invitationSettings;

    public function __construct()
    {
        $this->creationTime = new DateTimeImmutable();
        $this->invitationSettings = new InvitationSettings();
    }

    public function getInvitationSettings(): InvitationSettings
    {
        return $this->invitationSettings;
    }

    public function setInvitationSettings(InvitationSettings $invitationSettings): void
    {
        $this->invitationSettings = $invitationSettings;
    }

    #[Override]
    public function __toString(): string
    {
        return ($this->getName()) ?: '';
    }
}
