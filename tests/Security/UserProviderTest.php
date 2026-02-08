<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Domain;
use App\Entity\User;
use App\Entity\Voucher;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use App\Security\UserProvider;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProviderTest extends TestCase
{
    public function testLoadByUsernameSuccessful(): void
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findByEmail')->willReturnMap([
            ['admin@example.org', new User('admin@example.org')],
            ['admin', new User('admin@example.org')],
        ]);

        $domain = new Domain();
        $domain->setName('example.org');

        $domainRepository = $this->createStub(DomainRepository::class);
        $domainRepository->METHOD('getDefaultDomain')
            ->willReturn($domain);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnMap([
            [Domain::class, $domainRepository],
            [User::class, $userRepository],
        ]);

        $provider = new UserProvider($manager);

        self::assertInstanceOf(User::class, $provider->loadUserByIdentifier('admin'));
        self::assertInstanceOf(User::class, $provider->loadUserByIdentifier('admin@example.org'));
    }

    public function testLoadByUsernameException(): void
    {
        $this->expectException(UserNotFoundException::class);
        $repository = $this->createStub(UserRepository::class);
        $repository->method('findByEmail')->willReturnMap([
            ['admin@example.org', new User('admin@example.org')],
            ['admin', new User('admin@example.org')],
        ]);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $provider = new UserProvider($manager);

        $provider->loadUserByUsername('user');
    }

    public function testRefreshUserSuccessful(): void
    {
        $user = new User('test@example.org');
        $user->setId(1);

        $repository = $this->createStub(UserRepository::class);
        $repository->method('findOneBy')->willReturn($user);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $provider = new UserProvider($manager);

        $reloadedUser = $provider->refreshUser($user);

        self::assertEquals($user, $reloadedUser);
    }

    #[DataProvider('userProvider')]
    public function testRefreshUserException(string $userType, string $exception): void
    {
        if ($userType === 'mock') {
            $user = $this->createStub(UserInterface::class);
        } else {
            $user = new User('test@example.org');
            $user->setId(1);
        }

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(null);

        $domain = new Domain();
        $domain->setName('example.org');

        $domainRepository = $this->createStub(DomainRepository::class);
        $domainRepository->method('getDefaultDomain')
            ->willReturn($domain);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnMap([
            [Domain::class, $domainRepository],
            [User::class, $userRepository],
        ]);

        $provider = new UserProvider($manager);

        $this->expectException($exception);

        $provider->refreshUser($user);
    }

    public static function userProvider(): array
    {
        return [
            ['mock', UnsupportedUserException::class],
            ['real', UserNotFoundException::class],
        ];
    }

    public function testSupportClass(): void
    {
        $manager = $this->createStub(EntityManagerInterface::class);

        $repository = $this->createStub(DomainRepository::class);

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
