<?php

namespace App\Traits;

trait MailCryptSecretBoxTrait
{
    /**
     * @var string|null
     */
    private $mailCryptSecretBox;

    /**
     * @return string|null
     */
    public function getMailCryptSecretBox(): ?string
    {
        return $this->mailCryptSecretBox;
    }

    /**
     * @param string $mailCryptSecretBox
     */
    public function setMailCryptSecretBox(string $mailCryptSecretBox): void
    {
        $this->mailCryptSecretBox = $mailCryptSecretBox;
    }

    /**
     * @return bool
     */
    public function hasMailCryptSecretBox(): bool
    {
        return ($this->getMailCryptSecretBox()) ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseMailCryptSecretBox()
    {
        $this->mailCryptSecretBox = null;
    }
}
