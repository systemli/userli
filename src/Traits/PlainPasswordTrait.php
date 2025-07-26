<?php

namespace App\Traits;

use App\Validator\PasswordPolicy;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait PlainPasswordTrait.
 */
trait PlainPasswordTrait
{
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
}
