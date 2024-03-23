<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait TwofactorBackupCodeTrait
{
    #[ORM\Column(type: 'array')]
    private array $totpBackupCodes = [];

    public function getBackupCodes(): array
    {
        return $this->totpBackupCodes ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function isBackupCode(string $code): bool
    {
        return in_array($code, $this->totpBackupCodes, true);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateBackupCode(string $code): void
    {
        $key = array_search($code, $this->totpBackupCodes, true);
        if (false !== $key) {
            unset($this->totpBackupCodes[$key]);
        }
    }

    public function clearBackupCodes(): void
    {
        $this->totpBackupCodes = [];
    }

    /**
     * {@inheritdoc}
     */
    public function addBackupCode(string $backupCode): void
    {
        if (!in_array($backupCode, $this->totpBackupCodes)) {
            $this->totpBackupCodes[] = $backupCode;
        }
    }

    public function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 6; ++$i) {
            $codes[] = (string) random_int(100000, 999999);
        }
        $this->totpBackupCodes = $codes;

        return $this->totpBackupCodes;
    }
}
