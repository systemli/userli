<?php

namespace App\Handler;

use App\Entity\Alias;
use App\Entity\User;
use App\Helper\PasswordGenerator;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;

class DeleteHandler
{
    /** @var PasswordUpdater */
    private $passwordUpdater;

    /** @var EntityManagerInterface */
    private $manager;

    /** @var WkdHandler */
    private $wkdHandler;

    /**
     * DeleteHandler constructor.
     */
    public function __construct(PasswordUpdater $passwordUpdater,
                                EntityManagerInterface $manager,
                                WkdHandler $wkdHandler)
    {
        $this->passwordUpdater = $passwordUpdater;
        $this->manager = $manager;
        $this->wkdHandler = $wkdHandler;
    }

    /**
     * @param User $user
     */
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
        $aliasRepository = $this->manager->getRepository('App:Alias');
        $aliases = $aliasRepository->findByUser($user);
        foreach ($aliases as $alias) {
            $this->deleteAlias($alias, $user);
        }

        // Set password to random new one
        $password = PasswordGenerator::generate();
        $user->setPlainPassword($password);
        $this->passwordUpdater->updatePassword($user);

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
    }
}
