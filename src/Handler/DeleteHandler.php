<?php

namespace App\Handler;

use App\Entity\Alias;
use App\Entity\User;
use App\Helper\PasswordGenerator;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;

class DeleteHandler
{
    /**
     * DeleteHandler constructor.
     */
    public function __construct(
        private readonly PasswordUpdater $passwordUpdater,
        private readonly EntityManagerInterface $manager,
        private readonly WkdHandler $wkdHandler
    ) {
    }

    public function deleteAlias(Alias $alias, User $user = null)
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

    public function deleteUser(User $user)
    {
        // Delete aliases of user
        $aliasRepository = $this->manager->getRepository(Alias::class);
        $aliases = $aliasRepository->findByUser($user);
        foreach ($aliases as $alias) {
            $this->deleteAlias($alias, $user);
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

        // Delete OpenPGP key from WKD if no other user owns mail handle
        if (!$this->wkdHandler->userToUserIdExists($user->getEmail())) {
            $this->wkdHandler->deleteKey($user->getEmail());
        }

        $this->manager->flush();
    }
}
