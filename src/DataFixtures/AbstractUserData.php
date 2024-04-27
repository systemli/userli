<?php

namespace App\DataFixtures;

use App\Entity\Domain;
use App\Entity\User;
use App\Helper\PasswordUpdater;
use Doctrine\Bundle\FixturesBundle\Fixture;

abstract class AbstractUserData extends Fixture
{
    private const PASSWORD = 'password';

    protected string $passwordHash;

    public function __construct(readonly PasswordUpdater $passwordUpdater)
    {
        $user = new User();
        $passwordUpdater->updatePassword($user, self::PASSWORD);
        $this->passwordHash = $user->getPassword();
    }

    protected function buildUser(Domain $domain, string $email, array $roles): User
    {
        $user = new User();
        $user->setDomain($domain);
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($this->passwordHash);

        return $user;
    }
}
