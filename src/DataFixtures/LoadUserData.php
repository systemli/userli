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
        ['email' => 'admin@example.org', 'roles' => [Roles::ADMIN], 'totp' => false],
        ['email' => 'user@example.org', 'roles' => [Roles::USER], 'totp' => false],
        ['email' => 'totp@example.org', 'roles' => [Roles::USER], 'totp' => true],
        ['email' => 'spam@example.org', 'roles' => [Roles::SPAM], 'totp' => false],
        ['email' => 'support@example.org', 'roles' => [Roles::MULTIPLIER], 'totp' => false],
        ['email' => 'suspicious@example.org', 'roles' => [Roles::SUSPICIOUS], 'totp' => false],
        ['email' => 'domain@example.com', 'roles' => [Roles::DOMAIN_ADMIN], 'totp' => false],
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

            $totpEnabled = $user['totp'];
            $user = $this->buildUser($domain, $email, $roles);
            if ($totpEnabled) {
                $user->setTotpSecret($this->totpAuthenticator->generateSecret());
                $user->setTotpConfirmed(true);
            }

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
