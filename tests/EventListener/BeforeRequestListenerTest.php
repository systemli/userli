<?php

namespace App\Tests\EventListener;

use App\Entity\Domain;
use App\Entity\Filter\DomainFilter;
use App\Entity\User;
use App\EventListener\BeforeRequestListener;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\FilterCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Security;

/**
 * @author tim <tim@systemli.org>
 */
class BeforeRequestListenerTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $repo;
    /**
     * @var MockObject
     */
    private $manager;
    /**
     * @var MockObject
     */
    private $security;
    /**
     * @var BeforeRequestListener
     */
    private $listener;

    public function setUp()
    {
        $this->repo = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager->method('getRepository')->willReturn($this->repo);
        $this->security = $this->getMockBuilder(Security::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new BeforeRequestListener($this->manager, $this->security);
    }

    /**
     * @dataProvider provider
     *
     * @param User|null      $user
     * @param bool      $isAdmin
     * @param User|null $returnValue
     */
    public function testGetNonAdminUser(?User $user, bool $isAdmin, ?User $returnValue)
    {
        $this->security->method('getUser')->willReturn($user);
        $this->security->method('isGranted')->willReturn($isAdmin);
        $this->repo->method('findByEmail')->willReturn($user);

        $this->assertEquals($returnValue, $this->listener->getNonAdminUser());
    }

    /**
     * @return array
     */
    public function provider(): array
    {
        $user = $this->getUser();
        return [
            [null, false, null],   // not logged in
            [null, true, null],    // not logged in, but thinks is admin
            [$user, true, null],   // logged in admin
            [$user, false, $user], // logged in user
        ];
    }

    public function testOnKernelRequest()
    {
        $user = $this->getUser();
        $this->security->method('getUser')->willReturn($user);
        $this->security->method('isGranted')->willReturn(false);
        $this->repo->method('findByEmail')->willReturn($user);

        $filter = $this->getMockBuilder(DomainFilter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filter->expects($this->once())->method('setParameter');
        $filterCollection = $this->getMockBuilder(FilterCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filterCollection->expects($this->once())->method('enable')
            ->willReturn($filter);
        $this->manager->expects($this->once())->method('getFilters')
            ->willReturn($filterCollection);

        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener->onKernelRequest($event);
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        $domain = new Domain();
        $domain->setId(1);
        $user = new User();
        $user->setDomain($domain);
        return $user;
    }
}
