<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Exception;
use App\Entity\Domain;
use App\Enum\Roles;
use Doctrine\Persistence\ObjectManager;

class LoadUserData extends AbstractUserData implements DependentFixtureInterface, FixtureGroupInterface
{
    private array $users = [
        ['email' => 'admin@example.org', 'roles' => [Roles::ADMIN]],
        ['email' => 'user@example.org', 'roles' => [Roles::USER]],
        ['email' => 'spam@example.org', 'roles' => [Roles::SPAM]],
        ['email' => 'support@example.org', 'roles' => [Roles::MULTIPLIER]],
        ['email' => 'suspicious@example.org', 'roles' => [Roles::SUSPICIOUS]],
        ['email' => 'domain@example.com', 'roles' => [Roles::DOMAIN_ADMIN]],
    ];

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->users as $user) {
            $email = $user['email'];
            $splitted = explode('@', (string)$email);
            $roles = $user['roles'];
            $domain = $manager->getRepository(Domain::class)->findOneBy(['name' => $splitted[1]]);

            $user = $this->buildUser($domain, $email, $roles);

            $manager->persist($user);
        }

        $manager->flush();
        $manager->clear();
    }

    public function getDependencies(): array
    {
        return [
            LoadDomainData::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['basic'];
    }
}
