<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Stores TOTP backup codes as a JSON array. Codes are invalidated (removed) on use.
 */
trait TwofactorBackupCodeTrait
{
    #[ORM\Column(type: Types::JSON)]
    private array $totpBackupCodes = [];

    public function setTotpBackupCodes(array $totpBackupCodes): void
    {
        $this->totpBackupCodes = $totpBackupCodes;
    }

    public function getTotpBackupCodes(): array
    {
        return $this->totpBackupCodes ?: [];
    }

    public function isBackupCode(string $code): bool
    {
        return in_array($code, $this->totpBackupCodes, true);
    }

    public function invalidateBackupCode(string $code): void
    {
        $key = array_search($code, $this->totpBackupCodes, true);
        if (false !== $key) {
            unset($this->totpBackupCodes[$key]);
        }
    }

    public function addBackupCode(string $backupCode): void
    {
        if (!in_array($backupCode, $this->totpBackupCodes)) {
            $this->totpBackupCodes[] = $backupCode;
        }
    }
}
