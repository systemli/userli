<?php

namespace App\Handler;

use App\Entity\User;
use App\Enum\MailCrypt;
use App\Event\UserEvent;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class UserRestoreHandler
{
    private MailCrypt $mailCrypt;

    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly PasswordUpdater $passwordUpdater,
        private readonly MailCryptKeyHandler $mailCryptKeyHandler,
        private readonly RecoveryTokenHandler $recoveryTokenHandler,
        private readonly EventDispatcherInterface $eventDispatcher,
        readonly int $mailCryptEnv,
    ) {
        $this->mailCrypt = MailCrypt::from($mailCryptEnv);
    }

    public function restoreUser(User $user, string $password): ?string {
        $user->setDeleted(false);
        $this->passwordUpdater->updatePassword($user, $password);

        // Generate MailCrypt key with new password (overwrites old MailCrypt key)
        $recoveryToken = null;
        if ($this->mailCrypt->isAtLeast(MailCrypt::ENABLED_ENFORCE_NEW_USERS)) {
            $this->mailCryptKeyHandler->create($user, $password, true);

            // Reset recovery token
            $this->recoveryTokenHandler->create($user);
            $recoveryToken = $user->getPlainRecoveryToken();
        }

        // Clear sensitive plaintext data from User object
        $user->eraseCredentials();

        $this->manager->flush();

    $this->eventDispatcher->dispatch(new UserEvent($user), UserEvent::USER_CREATED);

        return $recoveryToken;
    }
}
