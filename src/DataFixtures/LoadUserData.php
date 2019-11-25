<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Enum\Roles;
use App\Helper\PasswordUpdater;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData extends Fixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    const PASSWORD = 'password';

    private $users = [
        ['email' => 'admin@example.org', 'roles' => [Roles::ADMIN]],
        ['email' => 'user@example.org', 'roles' => [Roles::USER]],
        ['email' => 'support@example.org', 'roles' => [Roles::MULTIPLIER]],
        ['email' => 'suspicious@example.org', 'roles' => [Roles::SUSPICIOUS]],
        ['email' => 'domain@example.com', 'roles' => [Roles::DOMAIN_ADMIN]],
    ];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadStaticUsers($manager);
        $this->loadRandomUsers($manager);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
    }

    /**
     * @return PasswordUpdater
     */
    private function getPasswordUpdater()
    {
        return $this->container->get('App\Helper\PasswordUpdater');
    }

    /**
     * @param $domain
     * @param $email
     * @param $roles
     *
     * @return User
     */
    private function buildUser($domain, $email, $roles)
    {
        $user = new User();
        $user->setDomain($domain);
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPlainPassword(self::PASSWORD);

        $this->getPasswordUpdater()->updatePassword($user);

        return $user;
    }

    private function loadStaticUsers(ObjectManager $manager)
    {
        $domainRepository = $manager->getRepository('App:Domain');

        foreach ($this->users as $user) {
            $email = $user['email'];
            $splitted = explode('@', $email);
            $roles = $user['roles'];
            $domain = $domainRepository->findOneBy(['name' => $splitted[1]]);

            $user = $this->buildUser($domain, $email, $roles);

            $manager->persist($user);
            $manager->flush();
        }
    }

    private function loadRandomUsers(ObjectManager $manager)
    {
        $domainRepository = $manager->getRepository('App:Domain');

        for ($i = 0; $i < 500; ++$i) {
            $email = sprintf('%s@example.org', uniqid());
            $splitted = explode('@', $email);
            $roles = [Roles::USER];
            $domain = $domainRepository->findOneBy(['name' => $splitted[1]]);

            $user = $this->buildUser($domain, $email, $roles);
            $user->setCreationTime(new \DateTime(sprintf('-%s days', mt_rand(1, 25))));

            if (0 == $i % 20) {
                $user->setDeleted(true);
            }

            if (0 == $i % 30) {
                $user->setRoles(array_merge($user->getRoles(), [Roles::SUSPICIOUS]));
            }

            $manager->persist($user);
            $manager->flush();
        }
    }
}
