<?php

namespace App\Tests\Helper;

use App\Entity\Domain;
use App\Entity\User;
use App\Helper\AdminPasswordUpdater;
use App\Helper\PasswordUpdater;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use App\Security\Encoder\PasswordHashEncoder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class AdminPasswordUpdaterTest extends TestCase
{
    private string $defaultDomain;

    public function setUp(): void
    {
        $this->defaultDomain = 'example.org';
    }

    public function testUpdateAdminPassword(): void
    {
        $admin = new User();
        $admin->setPlainPassword('password');
        $admin->setPassword('impossible_login');

        $adminPasswordUpdater = new AdminPasswordUpdater(
            $this->getManager($admin),
            $this->getUpdater(),
            $this->defaultDomain);

        $adminPasswordUpdater->updateAdminPassword('newpassword');

        self::assertEquals('newpassword', $admin->getPlainPassword());
        self::assertNotEquals('impossible_login', $admin->getPassword());
    }

    public function getManager($object): MockObject
    {
        $domainRepo = $this->getMockBuilder(DomainRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $domainRepo->method('findByName')->willReturn(null);

        $userRepo = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userRepo->method('findByEmail')->willReturn($object);

        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $manager->method('getRepository')->willReturnMap(
            [
                [Domain::class, $domainRepo],
                [User::class, $userRepo],
            ]);

        return $manager;
    }

    public function getUpdater(): PasswordUpdater
    {
        $hasher = $this->getMockBuilder(PasswordHasherInterface::class)
            ->getMock();
        $passwordHasherFactory = $this->getMockBuilder(PasswordHasherFactoryInterface::class)
            ->getMock();
        $passwordHasherFactory->method('getPasswordHasher')->willReturn($hasher);

        return new PasswordUpdater($passwordHasherFactory);
    }
}
