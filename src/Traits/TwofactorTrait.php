<?php

namespace App\Traits;

use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;

trait TwofactorTrait
{
	/** @var string */
	private $totpSecret;

	/** @var bool */
	private $totpConfirmed;

	/**
	 * {@inheritdoc}
	 */
	public function isTotpAuthenticationEnabled(): bool {
		return $this->totpConfirmed;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTotpAuthenticationUsername(): string {
		return $this->getUsername();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface {
		// Settings that are compatible with Google Authenticator specification
		return new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
	}

	/**
	 * @param string|null $totpSecret
	 */
	public function setTotpSecret(?string $totpSecret): void {
		$this->totpSecret = $totpSecret;
	}

	/**
	 * @param bool $totpConfirmed
	 */
	public function setTotpConfirmed(bool $totpConfirmed): void {
		$this->totpConfirmed = $totpConfirmed;
	}
}
