<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Domain;

final class DomainAdminModel
{
    private bool $invitationEnabled = false;

    private int $invitationLimit = 0;

    public static function fromDomain(Domain $domain): self
    {
        $model = new self();
        $model->invitationEnabled = $domain->isInvitationEnabled();
        $model->invitationLimit = $domain->getInvitationLimit();

        return $model;
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
}
