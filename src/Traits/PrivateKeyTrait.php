<?php

namespace App\Traits;

trait PrivateKeyTrait
{
    /**
     * @var string|null
     */
    private $privateKey;

    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    public function setPrivateKey(?string $privateKey): void
    {
        $this->privateKey = $privateKey;
    }
}
