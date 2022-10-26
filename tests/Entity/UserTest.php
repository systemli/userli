<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Enum\Roles;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;

/**
 * Class UserTest.
 */
class UserTest extends TestCase
{
    public function testGetRoles(): void
    {
        $user = new User();
        $this->hasOnlyValidRoles($user->getRoles());

        $user->setRoles([Roles::SUSPICIOUS]);
        $this->hasOnlyValidRoles($user->getRoles());
    }

    /**
     * @param $roles Roles[]
     */
    public function hasOnlyValidRoles(array $roles): void
    {
        self::assertIsArray($roles);
        foreach ($roles as $role) {
            self::assertArrayHasKey($role, Roles::getAll());
        }
    }

    public function testUserIsUserByDefault(): void
    {
        $user = new User();
        self::assertTrue($user->hasRole(Roles::USER));
    }

    public function testHasRole(): void
    {
        $user = new User();
        $user->setRoles([Roles::DOMAIN_ADMIN]);
        self::assertTrue($user->hasRole(Roles::DOMAIN_ADMIN));
        self::assertFalse($user->hasRole(Roles::ADMIN));
    }

    public function testGetEncoderName(): void
    {
        $user = new User();
        self::assertEquals(null, $user->getEncoderName());
        $user->setPasswordVersion(1);
        self::assertEquals('legacy', $user->getEncoderName());
    }

    public function testPlainPassword(): void
    {
        $user = new User();
        self::assertEquals(null, $user->getPlainPassword());
        $user->setPlainPassword('test');
        self::assertEquals('test', $user->getPlainPassword());
        $user->eraseCredentials();
        self::assertEquals(null, $user->getPlainPassword());
    }

    public function testHasRecoverySecretBox(): void
    {
        $user = new User();
        self::assertFalse($user->hasRecoverySecretBox());
        $user->setRecoverySecretBox('testsecret');
        self::assertTrue($user->hasRecoverySecretBox());
    }

    public function testPlainRecoveryToken(): void
    {
        $user = new User();
        self::assertEquals(null, $user->getPlainRecoveryToken());
        $user->setPlainRecoveryToken('testtoken');
        self::assertEquals('testtoken', $user->getPlainRecoveryToken());
        $user->erasePlainRecoveryToken();
        self::assertEquals(null, $user->getPlainRecoveryToken());
    }

    public function testHasCreationTimeSet(): void
    {
        $user = new User();
        $today = new \DateTime();
        self::assertEquals($user->getCreationTime()->format('Y-m-d'), $today->format('Y-m-d'));
    }

    public function testHasUpdatedTimeSet(): void
    {
        $user = new User();
        $today = new \DateTime();
        self::assertEquals($user->getUpdatedTime()->format('Y-m-d'), $today->format('Y-m-d'));
    }

    public function testTotp(): void
    {
        // totpSecret and totpConfirmed
        $totpSecret = 'secret';
        $user = new User();
        self::assertEquals(false, $user->getTotpConfirmed());
        self::assertEquals(false, $user->isTotpAuthenticationEnabled());
        $user->setTotpSecret($totpSecret);
        self::assertEquals(false, $user->isTotpAuthenticationEnabled());
        $user->setTotpConfirmed(true);
        self::assertEquals(true, $user->getTotpConfirmed());
        self::assertEquals(true, $user->isTotpAuthenticationEnabled());

        // getTotpAuthenticationUsername
        $email = 'user@example.org';
        $user->setEmail($email);
        self::assertEquals($email, $user->getTotpAuthenticationUsername());

        // getTotpAuthenticationConfiguration
        $totpConfiguration = new TotpConfiguration($totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
        self::assertEquals($totpConfiguration, $user->getTotpAuthenticationConfiguration());
    }
}
