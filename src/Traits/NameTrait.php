<?php

namespace App\Traits;

use App\Validator\Lowercase;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait NameTrait
{
    #[ORM\Column(unique: true)]
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Lowercase]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
