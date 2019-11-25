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
        return ($this->getMailCryptPublicKey()) ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseMailCryptPublicKey()
    {
        $this->mailCryptPublicKey = null;
    }
}
