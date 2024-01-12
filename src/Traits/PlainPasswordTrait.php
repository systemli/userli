<?php

namespace App\Traits;

/**
 * Trait PlainPasswordTrait.
 */
trait PlainPasswordTrait
{
    /**
     * @var string|null
     */
    private ?string $plainPassword = null;

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
        $this->erasePlainMailCryptPrivateKey();
        $this->erasePlainRecoveryToken();
    }
}
