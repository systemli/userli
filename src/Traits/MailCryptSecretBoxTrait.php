<?php

namespace App\Traits;

trait MailCryptSecretBoxTrait
{
    /**
     * @var string|null
     */
    private $mailCryptSecretBox;

    public function getMailCryptSecretBox(): ?string
    {
        return $this->mailCryptSecretBox;
    }

    public function setMailCryptSecretBox(string $mailCryptSecretBox): void
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
