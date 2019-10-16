<?php

namespace App\Traits;

trait MailCryptTrait
{
    /**
     * @var bool
     */
    private $mailCrypt = false;

    /**
     * @return bool
     */
    public function hasMailCrypt(): bool
    {
        return (bool) $this->mailCrypt;
    }

    /**
     * @return bool
     */
    public function getMailCrypt(): bool
    {
        return $this->mailCrypt;
    }

    /**
     * @param bool $mailCrypt
     */
    public function setMailCrypt(bool $mailCrypt): void
    {
        $this->mailCrypt = $mailCrypt;
    }
}
