<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait MailCryptSecretBoxTrait
{
    #[ORM\Column(type: Types::TEXT, nullable: true)]
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
