<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait MailCryptTrait
{
    /** @ORM\Column(options={"default"=false}) */
    private bool $mailCrypt = false;

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
