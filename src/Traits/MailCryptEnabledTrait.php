<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait MailCryptEnabledTrait
{
    #[ORM\Column(options: ['default' => false], name: 'mail_crypt')]
    private bool $mailCryptEnabled = false;

    public function getMailCryptEnabled(): bool
    {
        return $this->mailCryptEnabled;
    }

    public function setMailCryptEnabled(bool $mailCrypt): void
    {
        $this->mailCryptEnabled = $mailCrypt;
    }
}
