<?php

namespace App\Traits;

trait NameTrait
{
    /**
     * @var string|null
     */
    private $name;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
