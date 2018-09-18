<?php

namespace AppBundle\Handler;

use AppBundle\Entity\User;
use AppBundle\Helper\PasswordGenerator;
use AppBundle\Helper\PasswordUpdater;
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
