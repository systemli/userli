<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Domain;
use App\Entity\User;
use App\Handler\MailCryptKeyHandler;
use App\Helper\PasswordUpdater;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;

abstract class AbstractUserData extends Fixture
{
    private const PASSWORD = 'password';

    protected string $passwordHash;

    public function __construct(
        public readonly PasswordUpdater $passwordUpdater,
        public readonly TotpAuthenticatorInterface $totpAuthenticator,
        public readonly MailCryptKeyHandler $mailCryptKeyHandler,
    ) {
        $user = new User('');
        $passwordUpdater->updatePassword($user, self::PASSWORD);
        $this->passwordHash = $user->getPassword();
    }

    protected function buildUser(Domain $domain, string $email, array $roles): User
    {
        $user = new User($email);
        $user->setDomain($domain);
        $user->setRoles($roles);
        $user->setPassword($this->passwordHash);

        return $user;
    }
}
