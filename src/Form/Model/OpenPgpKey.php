<?php

declare(strict_types=1);

namespace App\Form\Model;

final class OpenPgpKey
{
    private ?string $email = null;

    private ?string $keyFile = null;

    private ?string $keyText = null;

    private ?string $password = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }
}
