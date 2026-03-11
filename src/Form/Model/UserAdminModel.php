<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\User;

final class UserAdminModel
{
    private ?string $email = null;

    private ?string $plainPassword = null;

    /** @var string[] */
    private array $roles = [];

    private ?int $quota = null;

    /** @var array<string, int>|null */
    private ?array $smtpQuotaLimits = null;

    private bool $totpConfirmed = false;

    private bool $passwordChangeRequired = false;

    public static function fromUser(User $user): self
    {
        $model = new self();
        $model->email = $user->getEmail();
        $model->roles = $user->getRoles();
        $model->quota = $user->getQuota();
        $model->smtpQuotaLimits = $user->getSmtpQuotaLimits();
        $model->totpConfirmed = $user->isTotpAuthenticationEnabled();
        $model->passwordChangeRequired = $user->isPasswordChangeRequired();

        return $model;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getQuota(): ?int
    {
        return $this->quota;
    }

    public function setQuota(?int $quota): void
    {
        $this->quota = $quota;
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

    public function isTotpConfirmed(): bool
    {
        return $this->totpConfirmed;
    }

    public function setTotpConfirmed(bool $totpConfirmed): void
    {
        $this->totpConfirmed = $totpConfirmed;
    }

    public function isPasswordChangeRequired(): bool
    {
        return $this->passwordChangeRequired;
    }

    public function setPasswordChangeRequired(bool $passwordChangeRequired): void
    {
        $this->passwordChangeRequired = $passwordChangeRequired;
    }
}
