<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class InitUser
{
    #[Assert\Length(min: 12, minMessage: 'form.weak_password')]
    #[Assert\NotBlank]
    #[Assert\NotCompromisedPassword(skipOnError: true)]
    private string $password;

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
}
