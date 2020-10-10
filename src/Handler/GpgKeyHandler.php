<?php

namespace App\Handler;

use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgDataException;
use App\Exception\NoGpgKeyForUserException;
use Crypt_GPG;
use Crypt_GPG_Exception;
use Crypt_GPG_FileException;
use Crypt_GPG_Key;
use Crypt_GPG_NoDataException;
use Crypt_GPG_SubKey;
use RuntimeException;

/**
 * Class GpgKeyHandler.
 */
class GpgKeyHandler
{
    /** @var Crypt_GPG */
    private $gpg;

    /** @var string */
    private $tempDir;

    /** @var string */
    private $email;

    /** @var string|null */
    private $keyData;

    /** @var string|null */
    private $keyId;

    /** @var string|null */
    private $fingerprint;

    private function createTempDir(): string
    {
        $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'userli_'.mt_rand().microtime(true);
        if (!mkdir($concurrentDirectory = $path) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException('Failed to create directory: '.$concurrentDirectory);
        }

        return $path;
    }

    private function recursiveRemoveDir(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ('.' === $object || '..' === $object) {
                    continue;
                }
                $objectPath = $dir.DIRECTORY_SEPARATOR.$object;
                if (is_dir($objectPath) && !is_link($objectPath)) {
                    $this->recursiveRemoveDir($objectPath);
                } else {
                    unlink($objectPath);
                }
            }
            rmdir($dir);
        }
    }

    private function initializeGPGHome(): void
    {
        $this->tempDir = $this->createTempDir();

        try {
            $this->gpg = new Crypt_GPG(['homedir' => $this->tempDir]);
        } catch (Crypt_GPG_FileException | \PEAR_Exception $e) {
            $this->tearDownGPGHome();
            throw new RuntimeException('Failed to read GnuPG home directory: '.$e->getMessage());
        }
    }

    public function tearDownGPGHome(): void
    {
        if (is_dir($this->tempDir)) {
            $this->recursiveRemoveDir($this->tempDir);
        }
    }

    /**
     * @throws NoGpgDataException
     * @throws NoGpgKeyForUserException
     * @throws MultipleGpgKeysForUserException
     * @throws RuntimeException
     */
    public function import(string $email, string $data): void
    {
        $this->email = $email;
        $this->initializeGPGHome();

        try {
            $this->gpg->importKey($data);
        } catch (\Crypt_GPG_BadPassphraseException | Crypt_GPG_NoDataException | Crypt_GPG_Exception $e) {
            $this->tearDownGPGHome();
            throw new NoGpgDataException('Failed to import WKD key: '.$e->getMessage());
        }

        try {
            $keys = $this->gpg->getKeys($this->email);
        } catch (Crypt_GPG_Exception $e) {
            $this->tearDownGPGHome();
            throw new RuntimeException('Failed to read keys: '.$e->getMessage());
        }

        if (count($keys) < 1) {
            $this->tearDownGPGHome();
            throw new NoGpgKeyForUserException(sprintf('No key found for %s', $this->email));
        }

        if (count($keys) > 1) {
            $this->tearDownGPGHome();
            throw new MultipleGpgKeysForUserException(sprintf('More than one keys found for %s', $this->email));
        }

        try {
            $this->keyData = $this->gpg->exportPublicKey($this->email);
        } catch (Crypt_GPG_Exception | \Crypt_GPG_KeyNotFoundException $e) {
            $this->tearDownGPGHome();
            throw new RuntimeException('Failed to export key: '.$e->getMessage());
        }
    }

    public function getKey(): ?string
    {
        return $this->keyData;
    }

    public function getId(): ?string
    {
        if (null === $this->keyId) {
            try {
                if ($keys = $this->gpg->getKeys($this->email)) {
                    /** @var Crypt_GPG_Key $key */
                    $key = $keys[0];
                    /** @var Crypt_GPG_SubKey $subKey */
                    $subKey = $key->getPrimaryKey();
                    $this->keyId = $subKey->getId();
                }
            } catch (Crypt_GPG_Exception $e) {
                $this->tearDownGPGHome();
                throw new RuntimeException('Failed to get GnuPG key ID: '.$e->getMessage());
            }
        }

        return $this->keyId;
    }

    public function getFingerprint(): ?string
    {
        if (null === $this->fingerprint) {
            try {
                $this->fingerprint = $this->gpg->getFingerprint($this->email, Crypt_GPG::FORMAT_CANONICAL);
            } catch (Crypt_GPG_Exception $e) {
                $this->tearDownGPGHome();
                throw new RuntimeException('Failed to get GnuPG key fingerprint: '.$e->getMessage());
            }
        }

        return $this->fingerprint;
    }
}
