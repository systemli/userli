<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


trait EmailTrait
{
    #[ORM\Column(unique: true)]
    #[Assert\NotNull]
    #[Assert\Email]
    private ?string $email = '';

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}
