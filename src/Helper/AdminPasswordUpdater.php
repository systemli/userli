<?php


namespace App\Helper;


use App\Entity\Domain;
use App\Entity\User;
use App\Enum\Roles;
use Doctrine\Common\Persistence\ObjectManager;

class AdminPasswordUpdater
{
    /**
     * @var ObjectManager
     */
    private $manager;
    /**
     * @var PasswordUpdater
     */
    private $updater;
    /**
     * @var string
     */
    private $defaultDomain;

    public function __construct(ObjectManager $manager, PasswordUpdater $updater, $defaultDomain)
    {
        $this->manager = $manager;
        $this->updater = $updater;
        $this->defaultDomain = $defaultDomain;
    }

    /**
     * Set admin password
     * Create admin user in default domain if not existent
     */
    public function updateAdminPassword(string $password) {
        $domain = $this->getDefaultDomain();
        $adminEmail = 'admin@'.$this->defaultDomain;
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

    /**
     * Return default domain
     * Create if not existent before
     *
     * @return Domain
     */
    public function getDefaultDomain()
    {
        $domain = $this->manager->getRepository('App:Domain')->findByName($this->defaultDomain);
        if (null === $domain) {
            $domain = new Domain();
            $domain->setName($this->defaultDomain);
            $this->manager->persist($domain);
        }
        return $domain;
    }

}