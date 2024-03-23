<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait RandomTrait
{
    #[ORM\Column(options: ['default' => false])]
    private bool $random = false;

    public function isRandom(): bool
    {
        return $this->random;
    }

    public function setRandom(bool $random): void
    {
        $this->random = $random;
    }
}
