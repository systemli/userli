<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Enum\Roles;
use PHPUnit\Framework\TestCase;

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
    public function hasOnlyValidRoles($roles): void
    {
        self::assertIsArray($roles);
        foreach ($roles as $role) {
            self::assertArrayHasKey($role, Roles::getAll());
        }
    }

    public function testUserisUserByDefault(): void
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
        $cr = $user->getCreationTime();
        $today = new \DateTime();
        self::assertEquals($cr->format('Y-m-d'), $today->format('Y-m-d'));
    }
}
