<?php

declare(strict_types=1);

namespace App\Form\Model;

final class OpenPgpKey
{
    private ?string $keyFile = null;

    private ?string $keyText = null;

    public function getKeyFile(): ?string
    {
        return $this->keyFile;
    }

    public function setKeyFile(string $keyFile): void
    {
        $this->keyFile = $keyFile;
    }

    public function getKeyText(): ?string
    {
        return $this->keyText;
    }

    public function setKeyText(string $keyText): void
    {
        $this->keyText = $keyText;
    }
}
