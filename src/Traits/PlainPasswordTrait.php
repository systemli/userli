<?php

namespace App\Traits;

use App\Validator\Constraints\PasswordPolicy;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait PlainPasswordTrait.
 */
trait PlainPasswordTrait
{
    /**
     * @var string|null
     */
    #[PasswordPolicy]
    #[Assert\NotCompromisedPassword( skipOnError: 'true')]
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
