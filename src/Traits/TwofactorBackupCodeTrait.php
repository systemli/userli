<?php

namespace App\Traits;

trait TwofactorBackupCodeTrait
{
    /** @var array */
    private $totpBackupCodes = [];

	public function getBackupCodes(): array {
		return $this->totpBackupCodes;
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
		if ($key !== false) {
			unset ($this->totpBackupCodes[$key]);
		}
    }

	/**
	 * @return void
	 */
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

	/**
	 * @return array
	 */
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
