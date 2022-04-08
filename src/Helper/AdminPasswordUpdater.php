<?php

namespace App\Helper;

use App\Entity\Domain;
use App\Entity\User;
use App\Enum\Roles;
use Doctrine\ORM\EntityManagerInterface;

class AdminPasswordUpdater
{
    /**
     * @var EntityManagerInterface
     */
    private $manager;
    /**
     * @var PasswordUpdater
     */
    private $updater;

    public function __construct(EntityManagerInterface $manager, PasswordUpdater $updater)
    {
        $this->manager = $manager;
        $this->updater = $updater;
    }

    /**
     * Set admin password
     * Create admin user in default domain if not existent.
     */
    public function updateAdminPassword(string $password): void
    {
        $domain = $this->manager->getRepository('App:Domain')->getDefaultDomain();
        $adminEmail = 'postmaster@'.$domain;
        $admin = $this->manager->getRepository('App:User')->findByEmail($adminEmail);
        if (null === $admin) {
            // create admin user
            $admin = new User();
            $admin->setEmail($adminEmail);
            $admin->setRoles([Roles::ADMIN]);
            $admin->setDomain($domain);
        }
        $admin->setPlainPassword($password);
        $this->updater->updatePassword($admin);
        $this->manager->persist($admin);
        $this->manager->flush();
    }
}
