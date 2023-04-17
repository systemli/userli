<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait NameTrait
{
    /** @ORM\Column(unique=true) */
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
