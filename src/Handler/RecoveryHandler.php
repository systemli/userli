<?php

declare(strict_types=1);

namespace App\Handler;

use App\Dto\RecoveryResult;
use App\Entity\AppPassword;
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
    /**
     * Waiting period between starting the recovery process and resetting the password.
     */
    public const int PROCESS_DELAY_HOURS = 48;

    /**
     * Days after which a started recovery process expires and has to be started again.
     */
    private const int PROCESS_EXPIRE_DAYS = 30;

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

        if (null === $recoveryStartTime || self::isExpired($recoveryStartTime)) {
            // Recovery process gets started
            $recoveryStartTime = new DateTimeImmutable();
            $user->setRecoveryStartTime($recoveryStartTime);
            $this->manager->flush();
            $this->eventDispatcher->dispatch(new UserEvent($user), UserEvent::RECOVERY_PROCESS_STARTED);

            return new RecoveryResult(
                RecoveryStatus::Started,
                activeTime: self::getActiveTime($recoveryStartTime),
            );
        }

        if (self::isPending($recoveryStartTime)) {
            // Recovery process is pending, but waiting period didn't elapse yet
            return new RecoveryResult(
                RecoveryStatus::Pending,
                activeTime: self::getActiveTime($recoveryStartTime),
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

        // Recovery assumes a compromised account, so drop mail access an attacker may have added.
        // Scheduled before the first flush, so the password never changes while they are left behind.
        $this->manager->getRepository(AppPassword::class)->removeByUser($user);

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
     * Cancel a running recovery process, e.g. because the account owner didn't start it.
     *
     * The recovery token stays valid, so whoever knows it can start a new recovery process.
     */
    public function cancelRecovery(User $user): void
    {
        $user->eraseRecoveryStartTime();
        $this->manager->flush();
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
            if (null === $recoveryStartTime || self::isPending($recoveryStartTime) || self::isExpired($recoveryStartTime)) {
                // Waiting period not elapsed yet or recovery process expired
                return false;
            }
        }

        return $this->recoveryTokenHandler->verify($user, strtolower($recoveryToken));
    }

    /**
     * Point in time when the second step (setting a new password) becomes available.
     */
    public static function getActiveTime(DateTimeImmutable $recoveryStartTime): DateTimeImmutable
    {
        return $recoveryStartTime->add(new DateInterval(sprintf('PT%dH', self::PROCESS_DELAY_HOURS)));
    }

    /**
     * Point in time when a started recovery process expires.
     */
    public static function getExpireTime(DateTimeImmutable $recoveryStartTime): DateTimeImmutable
    {
        return $recoveryStartTime->add(new DateInterval(sprintf('P%dD', self::PROCESS_EXPIRE_DAYS)));
    }

    /**
     * Whether a recovery process has been started and not expired yet.
     */
    public static function isInProgress(?DateTimeImmutable $recoveryStartTime): bool
    {
        return null !== $recoveryStartTime && !self::isExpired($recoveryStartTime);
    }

    private static function isPending(DateTimeImmutable $recoveryStartTime): bool
    {
        return self::getActiveTime($recoveryStartTime) > new DateTimeImmutable();
    }

    private static function isExpired(DateTimeImmutable $recoveryStartTime): bool
    {
        return self::getExpireTime($recoveryStartTime) <= new DateTimeImmutable();
    }

    private function findUserByEmail(string $email): ?User
    {
        return $this->manager->getRepository(User::class)->findByEmail($email);
    }
}
