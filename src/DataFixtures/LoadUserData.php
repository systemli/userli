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
    private const PASSWORD = 'password';

    private array $users = [
        ['email' => 'admin@example.org', 'roles' => [Roles::ADMIN], 'totp' => false, 'mailcrypt' => false],
        ['email' => 'user@example.org', 'roles' => [Roles::USER], 'totp' => false, 'mailcrypt' => false],
        ['email' => 'user2@example.org', 'roles' => [Roles::USER], 'totp' => false, 'mailcrypt' => false],
        ['email' => 'mailcrypt@example.org', 'roles' => [Roles::USER], 'totp' => false, 'mailcrypt' => true],
        ['email' => 'totp@example.org', 'roles' => [Roles::USER], 'totp' => true, 'mailcrypt' => false],
        ['email' => 'spam@example.org', 'roles' => [Roles::SPAM], 'totp' => false, 'mailcrypt' => false],
        ['email' => 'support@example.org', 'roles' => [Roles::MULTIPLIER], 'totp' => false, 'mailcrypt' => false],
        ['email' => 'suspicious@example.org', 'roles' => [Roles::SUSPICIOUS], 'totp' => false, 'mailcrypt' => false],
        ['email' => 'domain@example.com', 'roles' => [Roles::DOMAIN_ADMIN], 'totp' => false, 'mailcrypt' => false],
        ['email' => 'deleted@example.org', 'roles' => [Roles::USER], 'totp' => false, 'mailcrypt' => false, 'deleted' => true],
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
            $deleted = array_key_exists('deleted', $user) && $user['deleted'];

            $totpEnabled = $user['totp'];
            $mailcryptEnabled = $user['mailcrypt'];

            $user = $this->buildUser($domain, $email, $roles);
            if ($totpEnabled) {
                $user->setTotpSecret($this->totpAuthenticator->generateSecret());
                $user->setTotpConfirmed(true);
            }
            if ($mailcryptEnabled) {
                $this->mailCryptKeyHandler->create($user, self::PASSWORD);
                $user->setMailCrypt(true);
            }
            if ($deleted) {
                $user->setDeleted(true);
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
