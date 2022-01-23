<?php

namespace App\Traits;

trait MailCryptPublicKeyTrait
{
    /**
     * @var string|null
     */
    private $mailCryptPublicKey;

    public function getMailCryptPublicKey(): ?string
    {
        return $this->mailCryptPublicKey;
    }

    public function setMailCryptPublicKey(string $mailCryptPublicKey): void
    {
        $this->mailCryptPublicKey = $mailCryptPublicKey;
    }

    public function hasMailCryptPublicKey(): bool
    {
        return (bool) $this->getMailCryptPublicKey();
    }

    public function eraseMailCryptPublicKey(): void
    {
        $this->mailCryptPublicKey = null;
    }
}
