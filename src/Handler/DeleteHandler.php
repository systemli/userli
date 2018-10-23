<?php

namespace App\Handler;

use App\Entity\Alias;
use App\Entity\User;
use App\Helper\PasswordGenerator;
use App\Helper\PasswordUpdater;
use Doctrine\Common\Persistence\ObjectManager;

class DeleteHandler
{
    /**
     * @var PasswordUpdater
     */
    private $passwordUpdater;
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * DeleteHandler constructor.
     *
     * @param PasswordUpdater $passwordUpdater
     * @param ObjectManager   $manager
     */
    public function __construct(PasswordUpdater $passwordUpdater, ObjectManager $manager)
    {
        $this->passwordUpdater = $passwordUpdater;
        $this->manager = $manager;
    }

    /**
     * @param Alias $alias
     * @param User  $user
     */
    public function deleteAlias(Alias $alias, User $user = null)
    {
        if (null !== $user) {
            if ($alias->getUser() !== $user) {
                return;
            }
        }

        $alias->setDeleted(true);
        $alias->setEmptyUser();
        $this->manager->flush();
    }

    /**
     * @param User $user
     */
    public function deleteUser(User $user)
    {
        $password = PasswordGenerator::generate();

        $user->setDeleted(true);
        $user->setPlainPassword($password);

        $this->passwordUpdater->updatePassword($user);

        $this->manager->flush();
    }
}
