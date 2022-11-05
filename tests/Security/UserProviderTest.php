<?php

namespace App\Tests\Security;

use App\Entity\Domain;
use App\Entity\User;
use App\Entity\Voucher;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use App\Security\UserProvider;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProviderTest extends TestCase
{
    public function testLoadByUsernameSuccessful(): void
    {
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->method('findByEmail')->willReturnMap([
            ['admin@example.org', new User()],
            ['admin', new User()],
        ]);

        $domain = new Domain();
        $domain->setName('example.org');

        $domainRepository = $this->getMockBuilder(DomainRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $domainRepository->METHOD('getDefaultDomain')
            ->willReturn($domain);

        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $manager->method('getRepository')->willReturnMap([
            [Domain::class, $domainRepository],
            [User::class, $userRepository],
        ]);

        $provider = new UserProvider($manager);

        self::assertInstanceOf(User::class, $provider->loadUserByUsername('admin'));
        self::assertInstanceOf(User::class, $provider->loadUserByUsername('admin@example.org'));
    }

    public function testLoadByUsernameException(): void
    {
        $this->expectException(UsernameNotFoundException::class);
        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->method('findByEmail')->willReturnMap([
            ['admin@example.org', new User()],
            ['admin', new User()],
        ]);

        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $manager->method('getRepository')->willReturn($repository);

        $provider = new UserProvider($manager);

        $provider->loadUserByUsername('user');
    }

    public function testRefreshUserSuccessful(): void
    {
        $user = new User();
        $user->setId(1);

        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->method('findOneBy')->willReturn($user);

        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $manager->method('getRepository')->willReturn($repository);

        $provider = new UserProvider($manager);

        $reloadedUser = $provider->refreshUser($user);

        self::assertEquals($user, $reloadedUser);
    }

    /**
     * @dataProvider userProvider
     */
    public function testRefreshUserException(UserInterface $user, $exception): void
    {
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->method('findOneBy')->willReturn(null);

        $domain = new Domain();
        $domain->setName('example.org');

        $domainRepository = $this->getMockBuilder(DomainRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $domainRepository->method('getDefaultDomain')
            ->willReturn($domain);

        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $manager->method('getRepository')->willReturnMap([
            [Domain::class, $domainRepository],
            [User::class, $userRepository],
        ]);

        $provider = new UserProvider($manager);

        $this->expectException($exception);

        $provider->refreshUser($user);
    }

    public function userProvider(): array
    {
        $user = new User();
        $user->setId(1);

        return [
            [$this->getMockBuilder(UserInterface::class)->getMock(), UnsupportedUserException::class],
            [$user, UsernameNotFoundException::class],
        ];
    }

    public function testSupportClass(): void
    {
        $manager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder(DomainRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $domain = new Domain();
        $domain->setName('example.com');

        $repository->method('getDefaultDomain')
            ->willReturn($domain);

        $manager->method('getRepository')->willReturn($repository);
        $provider = new UserProvider($manager);

        self::assertTrue($provider->supportsClass(User::class));
        self::assertFalse($provider->supportsClass(Voucher::class));
    }
}
