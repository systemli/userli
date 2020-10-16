<?php

namespace App\Handler;

use App\Entity\User;
use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgDataException;
use App\Exception\NoGpgKeyForUserException;
use App\Importer\GpgKeyImporter;
use App\Model\OpenPGPKeyInfo;
use Doctrine\Common\Persistence\ObjectManager;
use RuntimeException;
use Tuupola\Base32;

class OpenPGPWkdHandler
{
    /** @var ObjectManager */
    private $manager;

    /** @var string */
    private $wkdDirectory;

    /** @var string */
    private $wkdFormat;

    /**
     * OpenPGPWkdHandler constructor.
     */
    public function __construct(ObjectManager $manager,
                                string $wkdDirectory,
                                string $wkdFormat)
    {
        $this->manager = $manager;
        $this->wkdDirectory = $wkdDirectory;
        $this->wkdFormat = $wkdFormat;
    }

    private function getWkdPath(string $domain): string
    {
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
    public function importKey(User $user, string $key): OpenPGPKeyInfo
    {
        $openPgpKeyInfo = GpgKeyImporter::import($user->getEmail(), $key);

        $user->setWkdKey($openPgpKeyInfo->getData());
        $this->manager->flush();

        $this->exportKeyToWKD($user);

        return $openPgpKeyInfo;
    }

    public function getKey(User $user): OpenPGPKeyInfo
    {
        if (null === $key = $user->getWkdKey()) {
            return new OpenPGPKeyInfo();
        }

        return GpgKeyImporter::import($user->getEmail(), base64_decode($key));
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
        if (null === $key = $user->getWkdKey()) {
            return;
        }

        $wkdPath = $this->getWkdPath($user->getDomain());
        $localPart = explode('@', $user->getEmail())[0];
        $wkdHash = $this->wkdHash($localPart);
        $wkdKeyPath = $wkdPath.DIRECTORY_SEPARATOR.$wkdHash;

        file_put_contents($wkdKeyPath, base64_decode($key));
    }
}
