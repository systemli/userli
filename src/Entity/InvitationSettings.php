<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class InvitationSettings
{
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $enabled = false;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $limit = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 7])]
    private int $waitingPeriodDays = 7;

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
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
