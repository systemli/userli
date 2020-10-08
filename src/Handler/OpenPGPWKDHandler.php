<?php

namespace App\Handler;

use App\Entity\User;

class OpenPGPWKDHandler
{
    /** @var GPGKeyHandler */
    private $keyHandler;

    /**
     * OpenPGPWKDHandler constructor.
     *
     * @param GPGKeyHandler $keyHandler
     */
    public function __construct(GPGKeyHandler $keyHandler) {
        $this->keyHandler = $keyHandler;
    }

    /**
     * @param User   $user
     * @param string $key
     *
     * @return string|null
     */
    public function importKey(User $user, string $key): ?string {
        $this->keyHandler->import($user->getEmail(), $key);
        $fingerprint = $this->keyHandler->getFingerprint();
        $sanitizedKey = $this->keyHandler->getKey();
        $this->keyHandler->tearDownGPGHome();

        // TODO: really import key into database

        return $fingerprint;
    }
}
