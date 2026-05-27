<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Alias;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Entity\Voucher;
use App\Event\AliasDeletedEvent;
use App\Event\UserEvent;
use App\Helper\PasswordGenerator;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class UserLifecycleService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private PasswordUpdater $passwordUpdater,
        private MailCryptCredentialRotation $mailCryptCredentialRotation,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws Exception
     */
    public function reset(User $user, string $password): ?string
    {
        $this->passwordUpdater->updatePassword($user, $password);
        $recoveryToken = $this->mailCryptCredentialRotation->rotate($user, $password);

        $user->setTotpConfirmed(false);
        $user->setTotpSecret(null);
        $user->setTotpBackupCodes([]);

        $user->eraseCredentials();

        $this->manager->flush();

        if (!$user->isDeleted()) {
            $this->eventDispatcher->dispatch(new UserEvent($user), UserEvent::USER_RESET);
        }

        return $recoveryToken;
    }

    public function delete(User $user): void
    {
        $aliases = $this->manager->getRepository(Alias::class)->findByUserAcrossDomains($user);
        foreach ($aliases as $alias) {
            $alias->setDeleted(true);
            $alias->clearSensitiveData();
        }

        $vouchers = $this->manager->getRepository(Voucher::class)->findByUser($user);
        foreach ($vouchers as $voucher) {
            if (!$voucher->isRedeemed()) {
                $this->manager->remove($voucher);
            }
        }

        $notifications = $this->manager->getRepository(UserNotification::class)->findByUser($user);
        foreach ($notifications as $notification) {
            $this->manager->remove($notification);
        }

        $this->passwordUpdater->updatePassword($user, PasswordGenerator::generate());
        $user->eraseRecoveryStartTime();
        $user->eraseRecoverySecretBox();
        $user->eraseMailCryptPublicKey();
        $user->eraseMailCryptSecretBox();

        $user->setDeleted(true);

        $this->manager->flush();

        $customAliases = $this->manager->getRepository(Alias::class)->findByUserAcrossDomains($user, random: false);
        foreach ($customAliases as $customAlias) {
            $this->eventDispatcher->dispatch(new AliasDeletedEvent($customAlias), AliasDeletedEvent::CUSTOM);
        }

        $this->eventDispatcher->dispatch(new UserEvent($user), UserEvent::USER_DELETED);
    }

    /**
     * @throws Exception
     */
    public function restore(User $user, string $password): ?string
    {
        $recoveryToken = $this->reset($user, $password);

        $user->setDeleted(false);
        $this->manager->flush();

        $this->eventDispatcher->dispatch(new UserEvent($user), UserEvent::USER_RESTORED);

        return $recoveryToken;
    }
}
