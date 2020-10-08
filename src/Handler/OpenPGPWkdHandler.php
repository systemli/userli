<?php

namespace App\Handler;

use App\Entity\User;
use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgKeyForUserException;
use Doctrine\Common\Persistence\ObjectManager;

class OpenPGPWkdHandler
{
    /** @var ObjectManager */
    private $manager;

    /** @var GpgKeyHandler */
    private $keyHandler;

    /**
     * OpenPGPWkdHandler constructor.
     *
     * @param ObjectManager $manager
     * @param GpgKeyHandler $keyHandler
     */
    public function __construct(ObjectManager $manager, GpgKeyHandler $keyHandler) {
        $this->manager = $manager;
        $this->keyHandler = $keyHandler;
    }

    /**
     * @param User   $user
     * @param string $key
     *
     * @return string|null
     *
     * @throws NoGpgKeyForUserException
     * @throws MultipleGpgKeysForUserException
     */
    public function importKey(User $user, string $key): ?string {
        $this->keyHandler->import($user->getEmail(), $key);
        $fingerprint = $this->keyHandler->getFingerprint();
        $sanitizedKey = $this->keyHandler->getKey();
        $this->keyHandler->tearDownGPGHome();

        $user->setWkdKey($sanitizedKey);
        $this->manager->flush();
        // TODO: really import key into database

        return $fingerprint;
    }

    /**
     * @param User $user
     *
     * @return string|null
     */
    public function getKeyFingerprint(User $user): ?string {
        $key = $user->getWkdKey();

        if (null === $key) {
            return null;
        }

        $this->keyHandler->import($user->getEmail(), $key);
        $fingerprint = $this->keyHandler->getFingerprint();
        $this->keyHandler->tearDownGPGHome();

        return $fingerprint;
    }

    /**
     * @param User $user
     */
    public function deleteKey(User $user): void {
        $user->setWkdKey(null);
        $this->manager->flush();
    }

    public function exportWkdKeys(): void {
    }
}
