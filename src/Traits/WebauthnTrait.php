<?php

declare(strict_types=1);

namespace App\Traits;

use App\Entity\WebauthnCredential;
use App\Security\TwoFactor\WebauthnTwoFactorProvider;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

trait WebauthnTrait
{
    /** @var Collection<int, WebauthnCredential> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: WebauthnCredential::class, fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    private Collection $webauthnCredentials;

    /** Base64url encoded, shared by all credentials of the user */
    #[ORM\Column(nullable: true)]
    private ?string $webauthnUserHandle = null;

    /**
     * @return Collection<int, WebauthnCredential>
     */
    public function getWebauthnCredentials(): Collection
    {
        return $this->webauthnCredentials;
    }

    /**
     * Credentials removed from the collection are deleted on flush.
     */
    public function removeWebauthnCredentials(): void
    {
        $this->webauthnCredentials->clear();
    }

    public function getWebauthnUserHandle(): ?string
    {
        return $this->webauthnUserHandle;
    }

    public function setWebauthnUserHandle(?string $webauthnUserHandle): void
    {
        $this->webauthnUserHandle = $webauthnUserHandle;
    }

    public function isWebauthnAuthenticationEnabled(): bool
    {
        return $this->webauthnCredentials->count() > 0;
    }

    /**
     * Security keys are offered first, the other methods stay available in the login form.
     */
    public function getPreferredTwoFactorProvider(): ?string
    {
        return $this->isWebauthnAuthenticationEnabled() ? WebauthnTwoFactorProvider::ALIAS : null;
    }

    /**
     * True if any second factor (TOTP or WebAuthn) is enabled.
     */
    public function isTwofactorEnabled(): bool
    {
        return $this->isTotpAuthenticationEnabled() || $this->isWebauthnAuthenticationEnabled();
    }

    /**
     * Disables all second factors and removes the backup codes.
     */
    public function resetTwofactor(): void
    {
        $this->setTotpConfirmed(false);
        $this->setTotpSecret(null);
        $this->setTotpBackupCodes([]);
        $this->removeWebauthnCredentials();
    }
}
