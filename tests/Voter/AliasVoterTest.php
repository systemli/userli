<?php

declare(strict_types=1);

namespace App\Tests\Voter;

use App\Entity\Alias;
use App\Entity\User;
use App\Enum\Roles;
use App\Voter\AliasVoter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AliasVoterTest extends TestCase
{
    public function testSupportsReturnsTrueForDeleteAndAlias(): void
    {
        $alias = new Alias();
        $voter = new AliasVoter($this->createSecurity(false));
        $this->assertTrue($this->invokeSupports($voter, AliasVoter::DELETE, $alias));
    }

    public function testSupportsReturnsFalseForOtherAttribute(): void
    {
        $alias = new Alias();
        $voter = new AliasVoter($this->createSecurity(false));
        $this->assertFalse($this->invokeSupports($voter, 'OTHER_ATTRIBUTE', $alias));
    }

    public function testSupportsReturnsFalseForNonAliasSubject(): void
    {
        $user = new User('test@example.org');
        $voter = new AliasVoter($this->createSecurity(false));
        $this->assertFalse($this->invokeSupports($voter, AliasVoter::DELETE, $user));
    }

    public function testVoteOnAttributeReturnsTrueIfUserOwnsAlias(): void
    {
        $user = new User('test@example.org');
        $alias = new Alias();
        $alias->setRandom(true);
        $alias->setUser($user);
        $voter = new AliasVoter($this->createSecurity(false));
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->assertTrue($this->invokeVoteOnAttribute($voter, AliasVoter::DELETE, $alias, $token));
    }

    public function testVoteOnAttributeReturnsFalseIfUserDoesNotOwnAlias(): void
    {
        $user = new User('user@example.org');
        $otherUser = new User('other@example.org');
        $alias = new Alias();
        $alias->setRandom(true);
        $alias->setUser($otherUser);
        $voter = new AliasVoter($this->createSecurity(false));
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->assertFalse($this->invokeVoteOnAttribute($voter, AliasVoter::DELETE, $alias, $token));
    }

    public function testVoteOnAttributeReturnsFalseIfTokenUserIsNotUser(): void
    {
        $alias = new Alias();
        $voter = new AliasVoter($this->createSecurity(false));
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $this->assertFalse($this->invokeVoteOnAttribute($voter, AliasVoter::DELETE, $alias, $token));
    }

    public function testVoteOnAttributeAllowsAdminCustomAlias(): void
    {
        $user = new User('admin@example.org');
        $alias = new Alias();
        $alias->setUser($user); // custom (not random)
        $voter = new AliasVoter($this->createSecurity(true));
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->assertTrue($this->invokeVoteOnAttribute($voter, AliasVoter::DELETE, $alias, $token));
    }

    private function invokeSupports(AliasVoter $voter, string $attribute, $subject): bool
    {
        $ref = new ReflectionClass($voter);
        $method = $ref->getMethod('supports');
        $method->setAccessible(true);

        return $method->invoke($voter, $attribute, $subject);
    }

    private function createSecurity(bool $isAdmin): Security
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturnCallback(static function (string $role) use ($isAdmin) {
            if (!$isAdmin) {
                return false;
            }

            return in_array($role, [Roles::ADMIN, Roles::DOMAIN_ADMIN], true);
        });

        return $security;
    }

    private function invokeVoteOnAttribute(AliasVoter $voter, string $attribute, $subject, TokenInterface $token): bool
    {
        $ref = new ReflectionClass($voter);
        $method = $ref->getMethod('voteOnAttribute');
        $method->setAccessible(true);

        return $method->invoke($voter, $attribute, $subject, $token);
    }
}
