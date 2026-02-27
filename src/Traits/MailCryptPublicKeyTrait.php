<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Stores the user's MailCrypt public key (PEM-encoded EC secp521r1), used by Dovecot to encrypt incoming mail.
 */
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
