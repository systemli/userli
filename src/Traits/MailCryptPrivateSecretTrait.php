<?php

namespace App\Traits;

/**
 * @author doobry <doobry@systemli.org>
 */
trait MailCryptPrivateSecretTrait
{
    /**
     * @var string|null
     */
    private $mailCryptPrivateSecret;

    /**
     * @return string|null
     */
    public function getMailCryptPrivateSecret(): ?string
    {
        return $this->mailCryptPrivateSecret;
    }

    /**
     * @param string $mailCryptPrivateSecret
     */
    public function setMailCryptPrivateSecret(string $mailCryptPrivateSecret): void
    {
        $this->mailCryptPrivateSecret = $mailCryptPrivateSecret;
    }

    /**
     * @return bool
     */
    public function hasMailCryptPrivateSecret(): bool
    {
        return ($this->getMailCryptPrivateSecret()) ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseMailCryptPrivateSecret()
    {
        $this->mailCryptPrivateSecret = null;
    }
}
