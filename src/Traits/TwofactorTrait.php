<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;

/**
 * Provides TOTP two-factor authentication fields.
 */
trait TwofactorTrait
{
    #[ORM\Column(nullable: true)]
    private ?string $totpSecret = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $totpConfirmed = false;

    public function isTotpAuthenticationEnabled(): bool
    {
        return (bool) $this->totpConfirmed;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        // Settings that are compatible with Google Authenticator specification
        return new TotpConfiguration($this->totpSecret ?: '', TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    public function setTotpSecret(?string $totpSecret): void
    {
        $this->totpSecret = $totpSecret;
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function getTotpConfirmed(): bool
    {
        return (bool) $this->totpConfirmed;
    }

    public function setTotpConfirmed(bool $totpConfirmed): void
    {
        $this->totpConfirmed = $totpConfirmed;
    }
}
