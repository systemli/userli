<?php

declare(strict_types=1);

namespace App\Handler;

use App\Entity\Alias;
use App\Entity\User;
use App\Entity\Voucher;
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
        private WkdHandler $wkdHandler,
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
    }

    public function deleteUser(User $user): void
    {
        // Delete aliases of user
        $aliases = $this->manager->getRepository(Alias::class)->findByUser($user);
        foreach ($aliases as $alias) {
            $this->deleteAlias($alias, $user);
        }

        // Delete vouchers of user
        $vouchers = $this->manager->getRepository(Voucher::class)->findByUser($user);
        foreach ($vouchers as $voucher) {
            $this->manager->remove($voucher);
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

        // Delete OpenPGP key from WKD
        $this->wkdHandler->deleteKey($user->getEmail());

        // Flag user as deleted
        $user->setDeleted(true);

        $this->manager->flush();

        $this->eventDispatcher->dispatch(new UserEvent($user), UserEvent::USER_DELETED);
    }
}
