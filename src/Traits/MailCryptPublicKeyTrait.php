<?php

namespace App\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait MailCryptPublicKeyTrait
{
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $mailCryptPublicKey = null;

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
