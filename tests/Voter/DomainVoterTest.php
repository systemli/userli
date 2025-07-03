<?php

namespace App\Tests\Voter;

use ReflectionMethod;
use ReflectionClass;
use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Voter\DomainVoter;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Bundle\SecurityBundle\Security;

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

    public function setUp(): void
    {
        $this->domain = new Domain();
        $user = new User();
        $user->setDomain($this->domain);
        $repo = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo->method('findByEmail')->willReturn($user);
        $manager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $manager->method('getRepository')->willReturn($repo);
        $security = $this->getMockBuilder(Security::class)
            ->disableOriginalConstructor()
            ->getMock();
        $security->method('isGranted')->will(
            $this->onConsecutiveCalls(false, true)
        );
        $security->method('getUser')->willReturn($user);
        $this->voter = new DomainVoter($security, $manager);
    }

    public function testSupports(): void
    {
        $method = self::getMethod('supports');
        $this->assertTrue($method->invokeArgs($this->voter, ['ROLE_USERLI_ADMIN_USER_LIST', new User()]));
        $this->assertTrue($method->invokeArgs($this->voter, ['ROLE_USERLI_ADMIN_ALIAS_VIEW', new Alias()]));
    }

    public function testVoteOnAttribute(): void
    {
        $otherUser = new User();
        $otherUser->setDomain($this->domain);
        $token = $this->getMockBuilder(TokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $method = self::getMethod('voteOnAttribute');

        $this->assertTrue($method->invokeArgs($this->voter, ['ROLE_USERLI_ADMIN_USER_LIST', $otherUser, $token]));
        // FIXME: Is this https://github.com/systemli/userli/issues/145 ???
        // $otherUser->setDomain(new Domain());
        // $this->assertFalse($method->invokeArgs($this->voter, ['ROLE_USERLI_ADMIN_USER_LIST', $otherUser, $token]));
    }
}
