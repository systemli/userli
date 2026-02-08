<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\Domain;
use App\Entity\Filter\DomainFilter;
use App\Entity\User;
use App\EventListener\BeforeRequestListener;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class BeforeRequestListenerTest extends TestCase
{
    private Stub&UserRepository $repo;
    private Stub&EntityManagerInterface $manager;
    private Stub&Security $security;
    private BeforeRequestListener $listener;

    protected function setUp(): void
    {
        $this->repo = $this->createStub(UserRepository::class);
        $this->manager = $this->createStub(EntityManagerInterface::class);
        $this->manager->method('getRepository')->willReturn($this->repo);
        $this->security = $this->createStub(Security::class);

        $this->listener = new BeforeRequestListener($this->manager, $this->security);
    }

    #[DataProvider('provider')]
    public function testGetNonAdminUser(?User $user, bool $isAdmin, ?User $returnValue): void
    {
        $this->security->method('getUser')->willReturn($user);
        $this->security->method('isGranted')->willReturn($isAdmin);
        $this->repo->method('findByEmail')->willReturn($user);

        self::assertEquals($returnValue, $this->listener->getNonAdminUser());
    }

    public static function provider(): array
    {
        $domain = new Domain();
        $domain->setId(1);
        $user = new User('test@example.org');
        $user->setDomain($domain);

        return [
            [null, false, null],   // not logged in
            [null, true, null],    // not logged in, but thinks is admin
            [$user, true, null],   // logged in admin
            [$user, false, $user], // logged in user
        ];
    }

    public function testOnKernelRequest(): void
    {
        $user = $this->getUser();

        $repo = $this->createStub(UserRepository::class);
        $repo->method('findByEmail')->willReturn($user);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repo);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->willReturn(false);

        $listener = new BeforeRequestListener($manager, $security);

        $filter = $this->createMock(DomainFilter::class);
        $filter->expects($this->once())->method('setParameter');
        $filterCollection = $this->createMock(FilterCollection::class);
        $filterCollection->expects($this->once())->method('enable')
            ->willReturn($filter);
        $manager->expects($this->once())->method('getFilters')
            ->willReturn($filterCollection);

        $event = $this->createStub(RequestEvent::class);

        $listener->onKernelRequest($event);
    }

    public function getUser(): User
    {
        $domain = new Domain();
        $domain->setId(1);
        $user = new User('test@example.org');
        $user->setDomain($domain);

        return $user;
    }
}
