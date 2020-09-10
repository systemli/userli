<?php

namespace App\Tests\Voter;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Voter\DomainVoter;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;

class DomainVoterTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $voter;
    /**
     * @var Domain
     */
    private $domain;

    protected static function getMethod($name)
    {
        $class = new \ReflectionClass(DomainVoter::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function setUp(): void
    {
        $this->domain = new Domain();
        $user = new User();
        $user->setDomain($this->domain);
        $repo = $this->getMockBuilder((UserRepository::class))
            ->disableOriginalConstructor()
            ->getMock();
        $repo->method('findByEmail')->willReturn($user);
        $manager = $this->getMockBuilder(ObjectManager::class)
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

    public function testSupports()
    {
        $method = self::getMethod('supports');
        $this->assertTrue($method->invokeArgs($this->voter, ['ROLE_USERLI_ADMIN_USER_LIST', new User()]));
        $this->assertTrue($method->invokeArgs($this->voter, ['ROLE_USERLI_ADMIN_ALIAS_VIEW', new Alias()]));
    }

    public function testVoteOnAttribute()
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
