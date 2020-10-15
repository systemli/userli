<?php

namespace App\Handler;

use App\Entity\User;
use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgDataException;
use App\Exception\NoGpgKeyForUserException;
use App\Model\OpenPGPKey;
use Doctrine\Common\Persistence\ObjectManager;
use RuntimeException;
use Tuupola\Base32;

class OpenPGPWkdHandler
{
    /** @var ObjectManager */
    private $manager;

    /** @var GpgKeyHandler */
    private $keyHandler;

    /** @var string */
    private $wkdDirectory;

    /** @var string */
    private $wkdFormat;

    /**
     * OpenPGPWkdHandler constructor.
     */
    public function __construct(ObjectManager $manager,
                                GpgKeyHandler $keyHandler,
                                string $wkdDirectory,
                                string $wkdFormat)
    {
        $this->manager = $manager;
        $this->keyHandler = $keyHandler;
        $this->wkdDirectory = $wkdDirectory;
        $this->wkdFormat = $wkdFormat;
    }

    private function getWkdPath(string $domain): string {
        if ('advanced' === $this->wkdFormat) {
            $wkdPath = $this->wkdDirectory.DIRECTORY_SEPARATOR.strtolower($domain).DIRECTORY_SEPARATOR.'hu';
        } elseif ('simple' === $this->wkdFormat) {
            $wkdPath = $this->wkdDirectory.DIRECTORY_SEPARATOR.'hu';
        } else {
            throw new RuntimeException(sprintf('Error: unsupported WKD format: %s', $this->wkdFormat));
        }

        if (!is_dir($wkdPath) && !mkdir($concurrentDirectory = $wkdPath, 0775, true) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        return $wkdPath;
    }

    /**
     * @throws NoGpgDataException
     * @throws NoGpgKeyForUserException
     * @throws MultipleGpgKeysForUserException
     */
    public function importKey(User $user, string $key): OpenPGPKey
    {
        $this->keyHandler->import($user->getEmail(), $key);
        $wkdKey = new OpenPGPKey($this->keyHandler->getKey(), $this->keyHandler->getId(), $this->keyHandler->getFingerprint());
        $this->keyHandler->tearDownGPGHome();

        $user->setWkdKey($wkdKey->getData());
        $this->manager->flush();

        $this->exportKeyToWKD($user);

        return $wkdKey;
    }

    public function getKey(User $user): OpenPGPKey
    {
        if (null === $wkdKey = $user->getWkdKey()) {
            return new OpenPGPKey();
        }

        $this->keyHandler->import($user->getEmail(), base64_decode($wkdKey));
        $wkdKey = new OpenPGPKey($this->keyHandler->getKey(), $this->keyHandler->getId(), $this->keyHandler->getFingerprint());
        $this->keyHandler->tearDownGPGHome();

        return $wkdKey;
    }

    public function deleteKey(User $user): void
    {
        if (null === $user->getWkdKey()) {
            return;
        }

        $wkdPath = $this->getWkdPath($user->getDomain());
        $localPart = explode('@', $user->getEmail())[0];
        $wkdHash = $this->wkdHash($localPart);
        $wkdKeyPath = $wkdPath.DIRECTORY_SEPARATOR.$wkdHash;

        if (is_file($wkdKeyPath) && !unlink($wkdKeyPath)) {
            throw new RuntimeException(sprintf('Failed to remove key from WKD directory path %s', $wkdKeyPath));
        }

        $user->setWkdKey(null);
        $this->manager->flush();
    }

    /**
     * Encodes the email address local part according to the WKD Web Wey Directory RFC draft.
     * See https://tools.ietf.org/html/draft-koch-openpgp-webkey-service-10 for further information.
     */
    private function wkdHash(string $localPart): string
    {
        $base32Encoder = new Base32(['characters' => Base32::ZBASE32]);

        return $base32Encoder->encode(sha1(strtolower($localPart), true));
    }

    /**
     * @throws RuntimeException
     */
    public function exportKeyToWKD(User $user): void
    {
        if (null === $wkdKey = $user->getWkdKey()) {
            return;
        }

        $wkdPath = $this->getWkdPath($user->getDomain());
        $localPart = explode('@', $user->getEmail())[0];
        $wkdHash = $this->wkdHash($localPart);
        $wkdKeyPath = $wkdPath.DIRECTORY_SEPARATOR.$wkdHash;

        file_put_contents($wkdKeyPath, base64_decode($wkdKey));
    }
}
