<?php

declare(strict_types=1);

namespace App\Form\Model;

class OpenPgpKey
{
    private ?string $keyFile = null;

    private ?string $keyText = null;

    public function getKeyFile(): ?string
    {
        return $this->keyFile;
    }

    /**
     * @return $this
     */
    public function setKeyFile(string $keyFile): self
    {
        $this->keyFile = $keyFile;

        return $this;
    }

    public function getKeyText(): ?string
    {
        return $this->keyText;
    }

    /**
     * @return $this
     */
    public function setKeyText(string $keyText): self
    {
        $this->keyText = $keyText;

        return $this;
    }
}
