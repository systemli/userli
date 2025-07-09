<?php

namespace App\Handler;

use App\Entity\User;
use App\Enum\MailCrypt;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;

readonly class UserPasswordUpdateHandler
{
    public function __construct(
        private EntityManagerInterface $manager,
        private PasswordUpdater        $passwordUpdater,
        private MailCryptKeyHandler    $mailCryptKeyHandler,
        private int                    $mailCrypt
    )
    {
    }

    public function updatePassword(User $user, string $newPassword, ?string $oldPassword = null): void
    {
        $this->passwordUpdater->updatePassword($user, $newPassword);

        // If the user has a MailCrypt secret box and an old password is provided,
        // we update the MailCrypt keys using the old password.
        // If the user does not have a MailCrypt secret box, we create new keys.
        if ($user->hasMailCryptSecretBox() && $oldPassword !== null) {
            $this->mailCryptKeyHandler->update($user, $oldPassword, $newPassword);
        } else {
            // Update password, generate MailCrypt keys,
            // and create a new MailCrypt secret box if it doesn't exist.
            $mailCryptEnable = $this->mailCrypt >= MailCrypt::ENABLED_ENFORCE_NEW_USERS;
            $this->mailCryptKeyHandler->create($user, $newPassword, $mailCryptEnable);
        }

        $user->eraseCredentials();

        $this->manager->flush();
    }
}
