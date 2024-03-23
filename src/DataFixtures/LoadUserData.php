<?php

namespace App\DataFixtures;

use Exception;
use DateTime;
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

    private string $passwordHash;

    private array $users = [
        ['email' => 'admin@example.org', 'roles' => [Roles::ADMIN]],
        ['email' => 'user@example.org', 'roles' => [Roles::USER]],
        ['email' => 'spam@example.org', 'roles' => [Roles::SPAM]],
        ['email' => 'support@example.org', 'roles' => [Roles::MULTIPLIER]],
        ['email' => 'suspicious@example.org', 'roles' => [Roles::SUSPICIOUS]],
        ['email' => 'domain@example.com', 'roles' => [Roles::DOMAIN_ADMIN]],
    ];

    private ContainerInterface $container;
    public function __construct(private readonly PasswordUpdater $passwordUpdater)
    {
    }

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
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setPlainPassword(self::PASSWORD);
        $this->getPasswordUpdater()->updatePassword($user);
        $this->passwordHash = $user->getPassword();

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
        return $this->passwordUpdater;
    }

    private function buildUser(Domain $domain, string $email, array $roles): User
    {
        $user = new User();
        $user->setDomain($domain);
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($this->passwordHash);

        return $user;
    }

    private function loadStaticUsers(ObjectManager $manager): void
    {
        $domainRepository = $manager->getRepository(Domain::class);

        foreach ($this->users as $user) {
            $email = $user['email'];
            $splitted = explode('@', (string) $email);
            $roles = $user['roles'];
            $domain = $domainRepository->findOneBy(['name' => $splitted[1]]);

            $user = $this->buildUser($domain, $email, $roles);

            $manager->persist($user);
        }

        $manager->flush();
        $manager->clear();
    }

    /**
     * @throws Exception
     */
    private function loadRandomUsers(ObjectManager $manager): void
    {
        $domainRepository = $manager->getRepository(Domain::class);
        $domain = $domainRepository->findOneBy(['name' => 'example.org']);
        $roles = [Roles::USER];

        for ($i = 0; $i < 15000; ++$i) {
            $email = sprintf('user-%d@%s', $i, $domain->getName());

            $user = $this->buildUser($domain, $email, $roles);
            $user->setCreationTime(new DateTime(sprintf('-%s days', random_int(1, 25))));

            if (0 === $i % 20) {
                $user->setDeleted(true);
            }

            if (0 === $i % 30) {
                $user->setRoles(array_merge($user->getRoles(), [Roles::SUSPICIOUS]));
            }

            $manager->persist($user);

            if (($i % 100) === 0) {
                $manager->flush();
            }
        }

        $manager->flush();
        $manager->clear();
    }
}
