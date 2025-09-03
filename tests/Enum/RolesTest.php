<?php

namespace App\Tests\Enum;

use App\Enum\Roles;
use PHPUnit\Framework\TestCase;

class RolesTest extends TestCase
{
    public function testReachableRolesForAdmin(): void
    {
        $reachable = Roles::getReachableRoles([Roles::ADMIN]);

        $this->assertSame([
            Roles::ADMIN,
            Roles::DOMAIN_ADMIN,
            Roles::USER,
            Roles::PERMANENT,
            Roles::SPAM,
            Roles::MULTIPLIER,
            Roles::SUSPICIOUS,
        ], $reachable);
    }

    public function testReachableRolesForDomainAdmin(): void
    {
        $reachable = Roles::getReachableRoles([Roles::DOMAIN_ADMIN]);
        $this->assertSame([
            Roles::USER,
            Roles::PERMANENT,
        ], $reachable);
    }

    public function testReachableRolesEmptyInput(): void
    {
        $this->assertSame([], Roles::getReachableRoles([]));
    }

    public function testReachableRolesIgnoresUnknownRole(): void
    {
        $this->assertSame([], Roles::getReachableRoles(['ROLE_UNKNOWN']));
    }

    public function testReachableRolesNonRootRoleHasNoImplied(): void
    {
        // SPAM is not a root key in the hierarchy; expect none
        $this->assertSame([], Roles::getReachableRoles([Roles::SPAM]));
    }

    public function testReachableRolesMergesAndDeduplicates(): void
    {
        // ADMIN already implies everything DOMAIN_ADMIN implies; expect ADMIN's list
        $reachable = Roles::getReachableRoles([Roles::ADMIN, Roles::DOMAIN_ADMIN]);
        $this->assertSame([
            Roles::ADMIN,
            Roles::DOMAIN_ADMIN,
            Roles::USER,
            Roles::PERMANENT,
            Roles::SPAM,
            Roles::MULTIPLIER,
            Roles::SUSPICIOUS,
        ], $reachable);
    }
}

