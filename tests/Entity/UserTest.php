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
    public function testGetRoles()
    {
        $user = new User();
        $this->hasOnlyValidRoles($user->getRoles());

        $user->setRoles([Roles::SUSPICIOUS]);
        $this->hasOnlyValidRoles($user->getRoles());
    }

    /**
     * @param $roles Roles[]
     */
    public function hasOnlyValidRoles($roles)
    {
        $this->assertInternalType('array', $roles);
        foreach ($roles as $role) {
            $this->assertContains($role, Roles::getAll());
        }
    }

    public function testUserisUserByDefault()
    {
        $user = new User();
        $user->hasRole(Roles::USER);
    }

    public function testHasRole()
    {
        $user = new User();
        $user->setRoles([Roles::DOMAIN_ADMIN]);
        $this->assertTrue($user->hasRole(Roles::DOMAIN_ADMIN));
        $this->assertFalse($user->hasRole(Roles::ADMIN));
    }

    public function testGetEncoderName()
    {
        $user = new User();
        $this->assertEquals(null, $user->getEncoderName());
        $user->setPasswordVersion(1);
        $this->assertEquals('legacy', $user->getEncoderName());
    }

    public function testPlainPassword()
    {
        $user = new User();
        $this->assertEquals(null, $user->getPlainPassword());
        $user->setPlainPassword('test');
        $this->assertEquals('test', $user->getPlainPassword());
        $user->eraseCredentials();
        $this->assertEquals(null, $user->getPlainPassword());
    }
}
