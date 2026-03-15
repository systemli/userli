<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\GpgKeyResult;
use App\Exception\GpgKeyParserException;
use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgDataException;
use App\Exception\NoGpgKeyForUserException;
use Crypt_GPG;
use Crypt_GPG_BadPassphraseException;
use Crypt_GPG_Exception;
use Crypt_GPG_FileException;
use Crypt_GPG_Key;
use Crypt_GPG_KeyNotFoundException;
use Crypt_GPG_NoDataException;
use DateTimeImmutable;
use PEAR_Exception;

use const DIRECTORY_SEPARATOR;

class GpgKeyParser
{
    protected function createGpg(string $homedir): Crypt_GPG
    {
        return new Crypt_GPG(['homedir' => $homedir]);
    }

    /**
     * @throws NoGpgDataException
     * @throws NoGpgKeyForUserException
     * @throws MultipleGpgKeysForUserException
     * @throws GpgKeyParserException
     */
    public function parse(string $email, string $data): GpgKeyResult
    {
        $tempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'userli_'.mt_rand().microtime(true);
        if (!mkdir($concurrentDirectory = $tempDir) && !is_dir($concurrentDirectory)) {
            throw new GpgKeyParserException('Failed to create directory: '.$concurrentDirectory);
        }

        try {
            $gpg = $this->createGpg($tempDir);
            $gpg->setEngineOptions([
                'import' => sprintf('--import-filter keep-uid="uid =~ <%s> || uid = %s"', $email, $email),
            ]);
        } catch (Crypt_GPG_FileException|PEAR_Exception $e) {
            throw new GpgKeyParserException('Failed to read GnuPG home directory: '.$e->getMessage());
        }

        try {
            $gpg->importKey($data);
        } catch (Crypt_GPG_BadPassphraseException|Crypt_GPG_NoDataException|Crypt_GPG_Exception $e) {
            throw new NoGpgDataException('Failed to import WKD key: '.$e->getMessage());
        }

        try {
            /** @var Crypt_GPG_Key[] $keys */
            $keys = $gpg->getKeys(sprintf('<%s>', $email));
        } catch (Crypt_GPG_Exception $cryptGPGException) {
            throw new GpgKeyParserException('Failed to read keys: '.$cryptGPGException->getMessage());
        }

        if (empty($keys)) {
            throw new NoGpgKeyForUserException(sprintf('No key found for %s', $email));
        }

        if (count($keys) > 1) {
            throw new MultipleGpgKeysForUserException(sprintf('More than one keys found for %s', $email));
        }

        try {
            $keyData = base64_encode($gpg->exportPublicKey($email, false));
        } catch (Crypt_GPG_Exception|Crypt_GPG_KeyNotFoundException $e) {
            throw new GpgKeyParserException('Failed to export key: '.$e->getMessage());
        }

        $primaryKey = $keys[0]->getPrimaryKey();
        if (!$primaryKey) {
            throw new GpgKeyParserException('Failed to get GnuPG key ID.');
        }

        $keyId = $primaryKey->getId();
        $expireTime = null;
        if (0 !== $expireUnixTimestamp = $primaryKey->getExpirationDate()) {
            $expireTime = new DateTimeImmutable('@'.$expireUnixTimestamp);
        }

        try {
            $fingerprint = $gpg->getFingerprint($email, Crypt_GPG::FORMAT_CANONICAL);
        } catch (Crypt_GPG_Exception $cryptGPGException) {
            throw new GpgKeyParserException('Failed to get GnuPG key fingerprint: '.$cryptGPGException->getMessage());
        }

        return new GpgKeyResult(
            email: $email,
            keyId: $keyId,
            fingerprint: $fingerprint,
            expireTime: $expireTime,
            keyData: $keyData,
        );
    }
}
