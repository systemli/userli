<?php

declare(strict_types=1);

namespace App\Handler;

use App\Entity\Alias;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Entity\Voucher;
use App\Event\AliasDeletedEvent;
use App\Event\UserEvent;
use App\Helper\PasswordGenerator;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final readonly class DeleteHandler
{
    /**
     * DeleteHandler constructor.
     */
    public function __construct(
        private PasswordUpdater $passwordUpdater,
        private EntityManagerInterface $manager,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function deleteAlias(Alias $alias, ?User $user = null): void
    {
        if (null !== $user) {
            if ($alias->getUser() !== $user) {
                return;
            }
        }

        $alias->setDeleted(true);
        $alias->clearSensitiveData();

        $this->manager->flush();

        if (!$alias->isRandom()) {
            $this->eventDispatcher->dispatch(new AliasDeletedEvent($alias), AliasDeletedEvent::CUSTOM);
        }
    }

    public function deleteUser(User $user): void
    {
        // Delete aliases of user
        $aliases = $this->manager->getRepository(Alias::class)->findByUserAcrossDomains($user);
        foreach ($aliases as $alias) {
            $alias->setDeleted(true);
            $alias->clearSensitiveData();
        }

        // Delete vouchers of user that have not been redeemed
        $vouchers = $this->manager->getRepository(Voucher::class)->findByUser($user);
        foreach ($vouchers as $voucher) {
            if (!$voucher->isRedeemed()) {
                $this->manager->remove($voucher);
            }
        }

        // Delete notifications of user
        $notifications = $this->manager->getRepository(UserNotification::class)->findByUser($user);
        foreach ($notifications as $notification) {
            $this->manager->remove($notification);
        }

        // Set password to random new one
        $password = PasswordGenerator::generate();
        $this->passwordUpdater->updatePassword($user, $password);

        // Erase recovery token and related fields
        $user->eraseRecoveryStartTime();
        $user->eraseRecoverySecretBox();

        // Erase MailCrypt keys
        $user->eraseMailCryptPublicKey();
        $user->eraseMailCryptSecretBox();

        // Flag user as deleted
        $user->setDeleted(true);

        $this->manager->flush();

        // Get custom aliases from all domains
        $customAliases = $this->manager->getRepository(Alias::class)->findByUserAcrossDomains($user, random: false);
        foreach ($customAliases as $customAlias) {
            $this->eventDispatcher->dispatch(new AliasDeletedEvent($customAlias), AliasDeletedEvent::CUSTOM);
        }

        $this->eventDispatcher->dispatch(new UserEvent($user), UserEvent::USER_DELETED);
    }
}
