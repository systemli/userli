<?php

namespace App\Traits;

/**
 * @author doobry <doobry@systemli.org>
 */
trait MailCryptPublicKeyTrait
{
    /**
     * @var string|null
     */
    private $mailCryptPublicKey;

    /**
     * @return string|null
     */
    public function getMailCryptPublicKey(): ?string
    {
        return $this->mailCryptPublicKey;
    }

    /**
     * @param string $mailCryptPublicKey
     */
    public function setMailCryptPublicKey(string $mailCryptPublicKey): void
    {
        $this->mailCryptPublicKey = $mailCryptPublicKey;
    }

    /**
     * @return bool
     */
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
