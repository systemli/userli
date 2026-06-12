<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Enum\MailCrypt;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use Exception;

final readonly class MailCryptCredentialRotation
{
    public function __construct(
        private MailCryptKeyHandler $mailCryptKeyHandler,
        private RecoveryTokenHandler $recoveryTokenHandler,
        private SettingsService $settingsService,
    ) {
    }

    /**
     * @throws Exception
     */
    public function rotate(User $user, string $password): ?string
    {
        $mailCrypt = MailCrypt::from($this->settingsService->get('mail_crypt'));
        if (!$mailCrypt->isAtLeast(MailCrypt::ENABLED_ENFORCE_NEW_USERS)) {
            return null;
        }

        $this->mailCryptKeyHandler->create($user, $password, true);
        $this->recoveryTokenHandler->create($user);

        return $user->getPlainRecoveryToken();
    }
}
