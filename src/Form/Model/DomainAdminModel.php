<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Domain;

final class DomainAdminModel
{
    private bool $invitationEnabled = false;

    private int $invitationLimit = 0;

    private int $waitingPeriodDays = 7;

    public static function fromDomain(Domain $domain): self
    {
        $model = new self();
        $settings = $domain->getInvitationSettings();
        $model->invitationEnabled = $settings->isEnabled();
        $model->invitationLimit = $settings->getLimit();
        $model->waitingPeriodDays = $settings->getWaitingPeriodDays();

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

    public function getWaitingPeriodDays(): int
    {
        return $this->waitingPeriodDays;
    }

    public function setWaitingPeriodDays(int $waitingPeriodDays): void
    {
        $this->waitingPeriodDays = $waitingPeriodDays;
    }
}
