<?php

namespace App\Traits;

use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;

trait TwofactorTrait
{
    /** @var string */
    private $totpSecret;

    /** @var bool */
    private $totpConfirmed = false;

    /**
     * {@inheritdoc}
     */
    public function isTotpAuthenticationEnabled(): bool
    {
        return (bool) $this->totpConfirmed;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotpAuthenticationUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        // Settings that are compatible with Google Authenticator specification
        return new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    public function setTotpSecret(?string $totpSecret): void
    {
        $this->totpSecret = $totpSecret;
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
