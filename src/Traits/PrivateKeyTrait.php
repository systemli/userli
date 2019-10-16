<?php

namespace App\Traits;

trait PrivateKeyTrait
{
    /**
     * @var string|null
     */
    private $privateKey;

    /**
     * @return string|null
     */
    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    /**
     * @param string|null $privateKey
     */
    public function setPrivateKey($privateKey): void
    {
        $this->privateKey = $privateKey;
    }
}
