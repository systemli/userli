<?php

namespace App\Traits;

use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;

trait TwofactorTrait
{
	/** @var string */
	private $totpSecret;

	/**
	 * {@inheritdoc}
	 */
	public function isTotpAuthenticationEnabled(): bool {
		return null !== $this->totpSecret;
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
}
