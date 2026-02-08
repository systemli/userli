<?php

declare(strict_types=1);

namespace App\Tests\Voter;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Voter\DomainVoter;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DomainVoterTest extends TestCase
{
    private DomainVoter $voter;
    private Domain $domain;

    protected static function getMethod($name): ReflectionMethod
    {
        $class = new ReflectionClass(DomainVoter::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    protected function setUp(): void
    {
        $this->domain = new Domain();
        $user = new User('test@example.org');
        $user->setDomain($this->domain);
        $repo = $this->createStub(UserRepository::class);
        $repo->method('findByEmail')->willReturn($user);
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repo);
        $security = $this->createStub(Security::class);
        $isGrantedCallCount = 0;
        $security->method('isGranted')->willReturnCallback(
            static function () use (&$isGrantedCallCount) {
                return [false, true][$isGrantedCallCount++];
            }
        );
        $security->method('getUser')->willReturn($user);
        $this->voter = new DomainVoter($security, $manager);
    }

    public function testSupports(): void
    {
        $method = self::getMethod('supports');
        self::assertTrue($method->invokeArgs($this->voter, ['ROLE_USERLI_ADMIN_USER_LIST', new User('test@example.org')]));
        self::assertTrue($method->invokeArgs($this->voter, ['ROLE_USERLI_ADMIN_ALIAS_VIEW', new Alias()]));
    }

    public function testVoteOnAttribute(): void
    {
        $otherUser = new User('other@example.org');
        $otherUser->setDomain($this->domain);
        $token = $this->createStub(TokenInterface::class);
        $method = self::getMethod('voteOnAttribute');

        self::assertTrue($method->invokeArgs($this->voter, ['ROLE_USERLI_ADMIN_USER_LIST', $otherUser, $token]));
        // FIXME: Is this https://github.com/systemli/userli/issues/145 ???
        // $otherUser->setDomain(new Domain());
        // self::assertFalse($method->invokeArgs($this->voter, ['ROLE_USERLI_ADMIN_USER_LIST', $otherUser, $token]));
    }
}
