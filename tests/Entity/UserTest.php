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
}
