<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Enum\MailCrypt;
use App\Event\UserEvent;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class UserResetService
{
    private MailCrypt $mailCrypt;

    public function __construct(
        private EntityManagerInterface $manager,
        private PasswordUpdater $passwordUpdater,
        private MailCryptKeyHandler $mailCryptKeyHandler,
        private RecoveryTokenHandler $recoveryTokenHandler,
        private EventDispatcherInterface $eventDispatcher,
        #[Autowire(env: 'MAIL_CRYPT')]
        int $mailCryptEnv,
    ) {
        $this->mailCrypt = MailCrypt::from($mailCryptEnv);
    }

    /**
     * Reset a user's password, MailCrypt keys, recovery token, and 2FA settings.
     *
     * @throws Exception if the user is deleted or MailCrypt key generation fails
     */
    public function resetUser(User $user, string $password): ?string
    {
        $this->passwordUpdater->updatePassword($user, $password);

        // Generate MailCrypt key with new password (overwrites old MailCrypt key)
        $recoveryToken = null;
        if ($this->mailCrypt->isAtLeast(MailCrypt::ENABLED_ENFORCE_NEW_USERS)) {
            $this->mailCryptKeyHandler->create($user, $password, true);

            // Reset recovery token
            $this->recoveryTokenHandler->create($user);
            $recoveryToken = $user->getPlainRecoveryToken();
        }

        // Reset twofactor settings
        $user->setTotpConfirmed(false);
        $user->setTotpSecret(null);
        $user->setTotpBackupCodes([]);

        // Clear sensitive plaintext data from User object
        $user->clearSensitiveData();

        $this->manager->flush();

        if (!$user->isDeleted()) {
            $this->eventDispatcher->dispatch(new UserEvent($user), UserEvent::USER_RESET);
        }

        return $recoveryToken;
    }
}
