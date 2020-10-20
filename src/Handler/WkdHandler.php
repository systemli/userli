<?php

namespace App\Handler;

use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgDataException;
use App\Exception\NoGpgKeyForUserException;
use App\Importer\GpgKeyImporter;
use App\Repository\OpenPgpKeyRepository;
use Doctrine\Common\Persistence\ObjectManager;
use RuntimeException;
use Tuupola\Base32;

class WkdHandler
{
    /** @var ObjectManager */
    private $manager;

    /** @var OpenPgpKeyRepository */
    private $repository;

    /** @var string */
    private $wkdDirectory;

    /** @var string */
    private $wkdFormat;

    /**
     * WkdHandler constructor.
     */
    public function __construct(ObjectManager $manager,
                                string $wkdDirectory,
                                string $wkdFormat)
    {
        $this->manager = $manager;
        $this->repository = $manager->getRepository('App:OpenPgpKey');
        $this->wkdDirectory = $wkdDirectory;
        $this->wkdFormat = $wkdFormat;
    }

    private function getWkdPath(string $domain): string
    {
        if ('advanced' === $this->wkdFormat) {
            $wkdPath = $this->wkdDirectory.DIRECTORY_SEPARATOR.strtolower($domain).DIRECTORY_SEPARATOR.'hu';
            $policyPath = $this->wkdDirectory.DIRECTORY_SEPARATOR.strtolower($domain).DIRECTORY_SEPARATOR.'policy';
        } elseif ('simple' === $this->wkdFormat) {
            $wkdPath = $this->wkdDirectory.DIRECTORY_SEPARATOR.'hu';
            $policyPath = $this->wkdDirectory.DIRECTORY_SEPARATOR.'policy';
        } else {
            throw new RuntimeException(sprintf('Error: unsupported WKD format: %s', $this->wkdFormat));
        }

        if (!is_dir($wkdPath) && !mkdir($concurrentDirectory = $wkdPath, 0775, true) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        if (!is_file($policyPath) && !touch($policyPath)) {
            throw new RuntimeException(sprintf('Policy file "%s" was not created', $policyPath));
        }

        return $wkdPath;
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

    private function getWkdKeyPath(string $email): string
    {
        [$localPart, $domain] = explode('@', $email);
        $wkdPath = $this->getWkdPath($domain);
        $wkdHash = $this->wkdHash($localPart);

        return $wkdPath.DIRECTORY_SEPARATOR.$wkdHash;
    }

    /**
     * @throws NoGpgDataException
     * @throws NoGpgKeyForUserException
     * @throws MultipleGpgKeysForUserException
     */
    public function importKey(string $key, string $email, ?User $user = null): OpenPgpKey
    {
        if (null === $openPgpKey = $this->repository->findByEmail($email)) {
            $openPgpKey = new OpenPgpKey();
        }

        if (null !== $user) {
            $openPgpKey->setUser($user);
        }

        $openPgpKeyNew = GpgKeyImporter::import($email, $key);

        $openPgpKey->setEmail($openPgpKeyNew->getEmail());
        $openPgpKey->setKeyId($openPgpKeyNew->getKeyId());
        $openPgpKey->setKeyFingerprint($openPgpKeyNew->getKeyFingerprint());
        $openPgpKey->setKeyExpireTime($openPgpKeyNew->getKeyExpireTime());
        $openPgpKey->setKeyData($openPgpKeyNew->getKeyData());

        $this->manager->persist($openPgpKey);
        $this->manager->flush();

        $this->exportKeyToWkd($openPgpKey);

        return $openPgpKey;
    }

    public function getKey(string $email): OpenPgpKey
    {
        if (null === $openPgpKey = $this->repository->findByEmail($email)) {
            $openPgpKey = new OpenPgpKey();
        }

        return $openPgpKey;
    }

    public function deleteKey(string $email): void
    {
        if (null === $openPgpKey = $this->repository->findByEmail($email)) {
            return;
        }

        $wkdKeyPath = $this->getWkdKeyPath($email);

        if (is_file($wkdKeyPath) && !unlink($wkdKeyPath)) {
            throw new RuntimeException(sprintf('Failed to remove key from WKD directory path %s', $wkdKeyPath));
        }

        $this->manager->remove($openPgpKey);
        $this->manager->flush();
    }

    /**
     * @throws RuntimeException
     */
    public function exportKeyToWkd(OpenPgpKey $openPgpKey): void
    {
        file_put_contents($this->getWkdKeyPath($openPgpKey->getEmail()), $openPgpKey->toBinary());
    }
}
