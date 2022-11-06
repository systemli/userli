<?php

namespace App\DataFixtures;

use App\Entity\Domain;
use App\Entity\User;
use App\Enum\Roles;
use App\Helper\PasswordUpdater;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData extends Fixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private const PASSWORD = 'password';

    private array $users = [
        ['email' => 'admin@example.org', 'roles' => [Roles::ADMIN]],
        ['email' => 'user@example.org', 'roles' => [Roles::USER]],
        ['email' => 'support@example.org', 'roles' => [Roles::MULTIPLIER]],
        ['email' => 'suspicious@example.org', 'roles' => [Roles::SUSPICIOUS]],
        ['email' => 'domain@example.com', 'roles' => [Roles::DOMAIN_ADMIN]],
    ];

    private ContainerInterface $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $this->loadStaticUsers($manager);
        $this->loadRandomUsers($manager);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder(): int
    {
        return 2;
    }

    private function getPasswordUpdater(): PasswordUpdater
    {
        return $this->container->get(PasswordUpdater::class);
    }

    /**
     * @param $domain
     * @param $email
     * @param $roles
     */
    private function buildUser($domain, $email, $roles): User
    {
        $user = new User();
        $user->setDomain($domain);
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPlainPassword(self::PASSWORD);

        $this->getPasswordUpdater()->updatePassword($user);

        return $user;
    }

    private function loadStaticUsers(ObjectManager $manager): void
    {
        $domainRepository = $manager->getRepository(Domain::class);

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

    /**
     * @throws \Exception
     */
    private function loadRandomUsers(ObjectManager $manager): void
    {
        $domainRepository = $manager->getRepository(Domain::classn);

        for ($i = 0; $i < 500; ++$i) {
            $email = sprintf('%s@example.org', uniqid('', true));
            $splitted = explode('@', $email);
            $roles = [Roles::USER];
            $domain = $domainRepository->findOneBy(['name' => $splitted[1]]);

            $user = $this->buildUser($domain, $email, $roles);
            $user->setCreationTime(new \DateTime(sprintf('-%s days', random_int(1, 25))));

            if (0 === $i % 20) {
                $user->setDeleted(true);
            }

            if (0 === $i % 30) {
                $user->setRoles(array_merge($user->getRoles(), [Roles::SUSPICIOUS]));
            }

            $manager->persist($user);
            $manager->flush();
        }
    }
}
