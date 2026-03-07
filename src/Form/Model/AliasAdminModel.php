<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Alias;
use App\Entity\User;

final class AliasAdminModel
{
    private ?string $source = null;

    private ?User $user = null;

    private ?string $destination = null;

    /** @var array<string, int>|null */
    private ?array $smtpQuotaLimits = null;

    public static function fromAlias(Alias $alias): self
    {
        $model = new self();
        $model->source = $alias->getSource();
        $model->user = $alias->getUser();
        $model->destination = $alias->getDestination();
        $model->smtpQuotaLimits = $alias->getSmtpQuotaLimits();

        return $model;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): void
    {
        $this->source = $source;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination): void
    {
        $this->destination = $destination;
    }

    /**
     * @return array<string, int>|null
     */
    public function getSmtpQuotaLimits(): ?array
    {
        return $this->smtpQuotaLimits;
    }

    /**
     * @param array<string, int>|null $smtpQuotaLimits
     */
    public function setSmtpQuotaLimits(?array $smtpQuotaLimits): void
    {
        $this->smtpQuotaLimits = $smtpQuotaLimits;
    }
}
