<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use App\Enum\Roles;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;

/**
 * Class UserTest.
 */
class UserTest extends TestCase
{
    public function testGetRoles(): void
    {
        $user = new User('test@example.org');
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
        $user = new User('test@example.org');
        self::assertTrue($user->hasRole(Roles::USER));
    }

    public function testHasRole(): void
    {
        $user = new User('test@example.org');
        $user->setRoles([Roles::DOMAIN_ADMIN]);
        self::assertTrue($user->hasRole(Roles::DOMAIN_ADMIN));
        self::assertFalse($user->hasRole(Roles::ADMIN));
    }

    public function testGetPasswordHasherName(): void
    {
        $user = new User('test@example.org');
        self::assertNull($user->getPasswordHasherName());
        $user->setPasswordVersion(1);
        self::assertEquals('legacy', $user->getPasswordHasherName());
    }

    public function testHasRecoverySecretBox(): void
    {
        $user = new User('test@example.org');
        self::assertFalse($user->hasRecoverySecretBox());
        $user->setRecoverySecretBox('testsecret');
        self::assertTrue($user->hasRecoverySecretBox());
    }

    public function testPlainRecoveryToken(): void
    {
        $user = new User('test@example.org');
        self::assertNull($user->getPlainRecoveryToken());
        $user->setPlainRecoveryToken('testtoken');
        self::assertEquals('testtoken', $user->getPlainRecoveryToken());
        $user->erasePlainRecoveryToken();
        self::assertNull($user->getPlainRecoveryToken());
    }

    public function testHasCreationTimeSet(): void
    {
        $user = new User('test@example.org');
        $today = new DateTimeImmutable();
        self::assertEquals($user->getCreationTime()->format('Y-m-d'), $today->format('Y-m-d'));
    }

    public function testUpdatedTimeIsNullBeforePersist(): void
    {
        $user = new User('test@example.org');
        self::assertNull($user->getUpdatedTime());
    }

    public function testTotp(): void
    {
        // totpSecret and totpConfirmed
        $totpSecret = 'secret';
        $user = new User('user@example.org');
        self::assertFalse($user->getTotpConfirmed());
        self::assertFalse($user->isTotpAuthenticationEnabled());
        $user->setTotpSecret($totpSecret);
        self::assertFalse($user->isTotpAuthenticationEnabled());
        $user->setTotpConfirmed(true);
        self::assertTrue($user->getTotpConfirmed());
        self::assertTrue($user->isTotpAuthenticationEnabled());

        // getTotpAuthenticationUsername
        $email = 'user@example.org';
        self::assertEquals($email, $user->getTotpAuthenticationUsername());

        // getTotpAuthenticationConfiguration
        $totpConfiguration = new TotpConfiguration($totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
        self::assertEquals($totpConfiguration, $user->getTotpAuthenticationConfiguration());
    }

    public function testTwofactorWithSecurityKey(): void
    {
        $user = new User('user@example.org');
        self::assertFalse($user->isWebauthnAuthenticationEnabled());
        self::assertFalse($user->isTwofactorEnabled());
        self::assertNull($user->getPreferredTwoFactorProvider());

        $user->getWebauthnCredentials()->add($this->createCredential($user));

        self::assertTrue($user->isWebauthnAuthenticationEnabled());
        self::assertTrue($user->isTwofactorEnabled());
        self::assertSame('webauthn', $user->getPreferredTwoFactorProvider());
    }

    public function testTwofactorWithTotpOnly(): void
    {
        $user = new User('user@example.org');
        $user->setTotpSecret('secret');
        $user->setTotpConfirmed(true);

        self::assertTrue($user->isTwofactorEnabled());
        self::assertNull($user->getPreferredTwoFactorProvider());
    }

    public function testResetTwofactor(): void
    {
        $user = new User('user@example.org');
        $user->setTotpSecret('secret');
        $user->setTotpConfirmed(true);
        $user->setTotpBackupCodes(['111111', '222222']);
        $user->getWebauthnCredentials()->add($this->createCredential($user));

        $user->resetTwofactor();

        self::assertFalse($user->isTwofactorEnabled());
        self::assertNull($user->getTotpSecret());
        self::assertSame([], $user->getTotpBackupCodes());
        self::assertCount(0, $user->getWebauthnCredentials());
    }

    private function createCredential(User $user): WebauthnCredential
    {
        return new WebauthnCredential($user, 'YubiKey', 'Y3JlZGVudGlhbA', 'cHVibGljLWtleQ', 'aGFuZGxl', 0, ['usb'], '00000000-0000-0000-0000-000000000000', 'none', false, false, false);
    }
}
