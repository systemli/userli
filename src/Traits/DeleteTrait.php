<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait DeleteTrait
{
    #[ORM\Column(options: ['default' => false])]
    private bool $deleted = false;

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }
}
