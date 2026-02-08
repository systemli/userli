<?php

declare(strict_types=1);

namespace App\Handler;

use App\Dto\RecoveryResult;
use App\Entity\User;
use App\Enum\RecoveryStatus;
use App\Event\UserEvent;
use App\Exception\RecoveryException;
use App\Helper\PasswordUpdater;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class RecoveryHandler
{
    private const string PROCESS_DELAY = '-2 days';

    private const string PROCESS_EXPIRE = '-30 days';

    public function __construct(
        private PasswordUpdater $passwordUpdater,
        private MailCryptKeyHandler $mailCryptKeyHandler,
        private RecoveryTokenHandler $recoveryTokenHandler,
        private EventDispatcherInterface $eventDispatcher,
        private EntityManagerInterface $manager,
    ) {
    }

    /**
     * Start or continue the recovery process for the given email and token.
     */
    public function startRecovery(string $email, string $recoveryToken): RecoveryResult
    {
        $user = $this->findUserByEmail($email);

        if (null === $user || !$this->recoveryTokenHandler->verify($user, strtolower($recoveryToken))) {
            return new RecoveryResult(RecoveryStatus::Invalid);
        }

        $recoveryStartTime = $user->getRecoveryStartTime();

        if (null === $recoveryStartTime || new DateTimeImmutable(self::PROCESS_EXPIRE) >= $recoveryStartTime) {
            // Recovery process gets started
            $user->updateRecoveryStartTime();
            $this->manager->flush();
            $this->eventDispatcher->dispatch(new UserEvent($user), UserEvent::RECOVERY_PROCESS_STARTED);

            // We don't have to add two days here, they will get added in `RecoveryProcessMessageSender`
            return new RecoveryResult(
                RecoveryStatus::Started,
                activeTime: $user->getRecoveryStartTime(),
            );
        }

        if (new DateTimeImmutable(self::PROCESS_DELAY) < $recoveryStartTime) {
            // Recovery process is pending, but waiting period didn't elapse yet
            return new RecoveryResult(
                RecoveryStatus::Pending,
                activeTime: $recoveryStartTime->add(new DateInterval('P2D')),
            );
        }

        // Recovery process successful, ready to reset password
        return new RecoveryResult(
            RecoveryStatus::Ready,
            recoveryToken: $recoveryToken,
        );
    }

    /**
     * Reset the password for the given email and recovery token.
     *
     * Returns the new recovery token that the user needs to save.
     *
     * @throws RecoveryException
     */
    public function resetPassword(string $email, string $recoveryToken, string $newPassword): string
    {
        $user = $this->findUserByEmail($email);

        if (null === $user || !$this->verifyRecoveryToken($user, $recoveryToken, true)) {
            throw new RecoveryException('Invalid email or recovery token');
        }

        $this->passwordUpdater->updatePassword($user, $newPassword);

        $mailCryptPrivateKey = $this->recoveryTokenHandler->decrypt($user, $recoveryToken);

        // Encrypt MailCrypt private key from recoverySecretBox with new password
        $this->mailCryptKeyHandler->updateWithPrivateKey($user, $mailCryptPrivateKey, $newPassword);

        // Clear old token
        $user->eraseRecoveryStartTime();
        $user->eraseRecoverySecretBox();

        // Generate new token
        $user->setPlainMailCryptPrivateKey($mailCryptPrivateKey);

        $this->recoveryTokenHandler->create($user);
        if (null === $newRecoveryToken = $user->getPlainRecoveryToken()) {
            throw new LogicException('PlainRecoveryToken should not be null');
        }

        // Reset twofactor settings
        $user->setTotpConfirmed(false);
        $user->setTotpSecret(null);
        $user->setTotpBackupCodes([]);

        // Clear sensitive plaintext data from User object
        $user->eraseCredentials();
        sodium_memzero($mailCryptPrivateKey);

        $this->manager->flush();

        return $newRecoveryToken;
    }

    /**
     * Verify that the recovery token is valid for the given email.
     */
    public function verifyRecoveryToken(string|User $emailOrUser, string $recoveryToken, bool $verifyTime = false): bool
    {
        $user = $emailOrUser instanceof User
            ? $emailOrUser
            : $this->findUserByEmail($emailOrUser);

        if (null === $user) {
            return false;
        }

        if ($verifyTime) {
            $recoveryStartTime = $user->getRecoveryStartTime();
            if (null === $recoveryStartTime || new DateTimeImmutable(self::PROCESS_DELAY) < $recoveryStartTime) {
                return false;
            }
        }

        return $this->recoveryTokenHandler->verify($user, strtolower($recoveryToken));
    }

    private function findUserByEmail(string $email): ?User
    {
        return $this->manager->getRepository(User::class)->findByEmail($email);
    }
}
