<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Entity\Voucher;
use App\Repository\UserRepository;
use App\Security\UserProvider;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProviderTest extends TestCase
{
    public function testLoadByUsernameSuccessful()
    {
        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())->method('findByEmail')->willReturnMap([
            ['admin@example.org', new User()],
            ['admin', new User()],
        ]);

        $manager = $this->getMockBuilder(ObjectManager::class)->getMock();
        $manager->expects($this->any())->method('getRepository')->willReturn($repository);
        $defaultDomain = 'example.org';

        $provider = new UserProvider($manager, $defaultDomain);

        self::assertInstanceOf(User::class, $provider->loadUserByUsername('admin'));
        self::assertInstanceOf(User::class, $provider->loadUserByUsername('admin@example.org'));
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadByUsernameException()
    {
        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())->method('findByEmail')->willReturnMap([
            ['admin@example.org', new User()],
            ['admin', new User()],
        ]);

        $manager = $this->getMockBuilder(ObjectManager::class)->getMock();
        $manager->expects($this->any())->method('getRepository')->willReturn($repository);
        $defaultDomain = 'example.org';

        $provider = new UserProvider($manager, $defaultDomain);

        $provider->loadUserByUsername('user');
    }

    public function testRefreshUserSuccessful()
    {
        $user = new User();
        $user->setId(1);

        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())->method('findOneBy')->willReturn($user);

        $manager = $this->getMockBuilder(ObjectManager::class)->getMock();
        $manager->expects($this->any())->method('getRepository')->willReturn($repository);
        $defaultDomain = 'example.org';

        $provider = new UserProvider($manager, $defaultDomain);

        $reloadedUser = $provider->refreshUser($user);

        self::assertEquals($user, $reloadedUser);
    }

    /**
     * @dataProvider userProvider
     *
     * @param UserInterface $user
     */
    public function testRefreshUserException(UserInterface $user, $exception)
    {
        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())->method('findOneBy')->willReturn(null);

        $manager = $this->getMockBuilder(ObjectManager::class)->getMock();
        $manager->expects($this->any())->method('getRepository')->willReturn($repository);
        $defaultDomain = 'example.org';

        $provider = new UserProvider($manager, $defaultDomain);

        $this->expectException($exception);

        $provider->refreshUser($user);
    }

    public function userProvider()
    {
        return [
            [$this->getMockBuilder(UserInterface::class)->getMock(), UnsupportedUserException::class],
            [new User(), UsernameNotFoundException::class],
        ];
    }

    public function testSupportClass()
    {
        $defaultDomain = 'example.org';
        $manager = $this->getMockBuilder(ObjectManager::class)->getMock();
        $provider = new UserProvider($manager, $defaultDomain);

        self::assertTrue($provider->supportsClass(User::class));
        self::assertFalse($provider->supportsClass(Voucher::class));
    }
}
