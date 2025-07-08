<?php

namespace App\Tests\Voter;

use App\Entity\Alias;
use App\Entity\User;
use App\Voter\AliasVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AliasVoterTest extends TestCase
{
    public function testSupportsReturnsTrueForDeleteAndAlias(): void
    {
        $voter = new AliasVoter();
        $alias = new Alias();
        $this->assertTrue($this->invokeSupports($voter, AliasVoter::DELETE, $alias));
    }

    public function testSupportsReturnsFalseForOtherAttribute(): void
    {
        $voter = new AliasVoter();
        $alias = new Alias();
        $this->assertFalse($this->invokeSupports($voter, 'OTHER_ATTRIBUTE', $alias));
    }

    public function testSupportsReturnsFalseForNonAliasSubject(): void
    {
        $voter = new AliasVoter();
        $user = new User();
        $this->assertFalse($this->invokeSupports($voter, AliasVoter::DELETE, $user));
    }

    public function testVoteOnAttributeReturnsTrueIfUserOwnsAlias(): void
    {
        $voter = new AliasVoter();
        $user = new User();
        $alias = new Alias();
        $alias->setRandom(true);
        $alias->setUser($user);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->assertTrue($this->invokeVoteOnAttribute($voter, AliasVoter::DELETE, $alias, $token));
    }

    public function testVoteOnAttributeReturnsFalseIfUserDoesNotOwnAlias(): void
    {
        $voter = new AliasVoter();
        $user = new User();
        $otherUser = new User();
        $alias = new Alias();
        $alias->setRandom(true);
        $alias->setUser($otherUser);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->assertFalse($this->invokeVoteOnAttribute($voter, AliasVoter::DELETE, $alias, $token));
    }

    public function testVoteOnAttributeReturnsFalseIfTokenUserIsNotUser(): void
    {
        $voter = new AliasVoter();
        $alias = new Alias();
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $this->assertFalse($this->invokeVoteOnAttribute($voter, AliasVoter::DELETE, $alias, $token));
    }

    private function invokeSupports(AliasVoter $voter, string $attribute, $subject): bool
    {
        $ref = new \ReflectionClass($voter);
        $method = $ref->getMethod('supports');
        $method->setAccessible(true);
        return $method->invoke($voter, $attribute, $subject);
    }

    private function invokeVoteOnAttribute(AliasVoter $voter, string $attribute, $subject, TokenInterface $token): bool
    {
        $ref = new \ReflectionClass($voter);
        $method = $ref->getMethod('voteOnAttribute');
        $method->setAccessible(true);
        return $method->invoke($voter, $attribute, $subject, $token);
    }
}
