<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait MailCryptSecretBoxTrait
{
    /** @ORM\Column(type="text", nullable=true) */
    private ?string $mailCryptSecretBox = null;

    public function getMailCryptSecretBox(): ?string
    {
        return $this->mailCryptSecretBox;
    }

    public function setMailCryptSecretBox(?string $mailCryptSecretBox): void
    {
        $this->mailCryptSecretBox = $mailCryptSecretBox;
    }

    public function hasMailCryptSecretBox(): bool
    {
        return (bool) $this->getMailCryptSecretBox();
    }

    public function eraseMailCryptSecretBox(): void
    {
        $this->mailCryptSecretBox = null;
    }
}
