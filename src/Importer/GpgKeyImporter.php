<?php

namespace App\Importer;

use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgDataException;
use App\Exception\NoGpgKeyForUserException;
use App\Model\OpenPGPKeyInfo;
use Crypt_GPG;
use Crypt_GPG_Exception;
use Crypt_GPG_FileException;
use Crypt_GPG_Key;
use Crypt_GPG_NoDataException;
use RuntimeException;

/**
 * Class GpgKeyImporter.
 */
class GpgKeyImporter implements OpenPgpKeyImporterInterface
{
    private static function recursiveRemoveDir(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ('.' === $object || '..' === $object) {
                    continue;
                }
                $objectPath = $dir.DIRECTORY_SEPARATOR.$object;
                if (is_dir($objectPath) && !is_link($objectPath)) {
                    self::recursiveRemoveDir($objectPath);
                } else {
                    unlink($objectPath);
                }
            }
            rmdir($dir);
        }
    }

    /**
     * @throws NoGpgDataException
     * @throws NoGpgKeyForUserException
     * @throws MultipleGpgKeysForUserException
     * @throws RuntimeException
     */
    public static function import(string $email, string $data): OpenPGPKeyInfo
    {
        $tempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'userli_'.mt_rand().microtime(true);
        if (!mkdir($concurrentDirectory = $tempDir) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException('Failed to create directory: '.$concurrentDirectory);
        }

        try {
            $gpg = new Crypt_GPG(['homedir' => $tempDir]);
        } catch (Crypt_GPG_FileException | \PEAR_Exception $e) {
            self::recursiveRemoveDir($tempDir);
            throw new RuntimeException('Failed to read GnuPG home directory: '.$e->getMessage());
        }

        try {
            $gpg->importKey($data);
        } catch (\Crypt_GPG_BadPassphraseException | Crypt_GPG_NoDataException | Crypt_GPG_Exception $e) {
            self::recursiveRemoveDir($tempDir);
            throw new NoGpgDataException('Failed to import WKD key: '.$e->getMessage());
        }

        try {
            /** @var Crypt_GPG_Key[] $keys */
            $keys = $gpg->getKeys($email);
        } catch (Crypt_GPG_Exception $e) {
            self::recursiveRemoveDir($tempDir);
            throw new RuntimeException('Failed to read keys: '.$e->getMessage());
        }

        if (count($keys) < 1) {
            self::recursiveRemoveDir($tempDir);
            throw new NoGpgKeyForUserException(sprintf('No key found for %s', $email));
        }

        if (count($keys) > 1) {
            self::recursiveRemoveDir($tempDir);
            throw new MultipleGpgKeysForUserException(sprintf('More than one keys found for %s', $email));
        }

        try {
            $keyData = base64_encode($gpg->exportPublicKey($email, false));
        } catch (Crypt_GPG_Exception | \Crypt_GPG_KeyNotFoundException $e) {
            self::recursiveRemoveDir($tempDir);
            throw new RuntimeException('Failed to export key: '.$e->getMessage());
        }

        $primaryKey = $keys[0]->getPrimaryKey();
        if ($primaryKey) {
            $keyId = $primaryKey->getId();
        } else {
            self::recursiveRemoveDir($tempDir);
            throw new RuntimeException('Failed to get GnuPG key ID.');
        }

        try {
            $fingerprint = $gpg->getFingerprint($email, Crypt_GPG::FORMAT_CANONICAL);
        } catch (Crypt_GPG_Exception $e) {
            self::recursiveRemoveDir($tempDir);
            throw new RuntimeException('Failed to get GnuPG key fingerprint: '.$e->getMessage());
        }

        self::recursiveRemoveDir($tempDir);

        return new OpenPGPKeyInfo($keyData, $keyId, $fingerprint);
    }
}
