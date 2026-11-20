<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class AliasConvert
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 12, minMessage: 'form.weak_password')]
    #[Assert\NotCompromisedPassword(skipOnError: true)]
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
