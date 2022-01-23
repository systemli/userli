<?php

namespace App\Traits;

trait MailCryptTrait
{
    /**
     * @var bool
     */
    private $mailCrypt = false;

    public function hasMailCrypt(): bool
    {
        return $this->mailCrypt;
    }

    public function getMailCrypt(): bool
    {
        return $this->mailCrypt;
    }

    public function setMailCrypt(bool $mailCrypt): void
    {
        $this->mailCrypt = $mailCrypt;
    }
}
