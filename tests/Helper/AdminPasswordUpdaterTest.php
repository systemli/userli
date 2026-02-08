<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Entity\Domain;
use App\Entity\User;
use App\Helper\AdminPasswordUpdater;
use App\Helper\PasswordUpdater;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PlaintextPasswordHasher;

class AdminPasswordUpdaterTest extends TestCase
{
    private string $defaultDomain;

    protected function setUp(): void
    {
        $this->defaultDomain = 'example.org';
    }

    public function testUpdateAdminPassword(): void
    {
        $admin = new User('postmaster@example.org');
        $admin->setPassword('impossible_login');

        $adminPasswordUpdater = new AdminPasswordUpdater(
            $this->getManager($admin),
            $this->getUpdater(),
            $this->defaultDomain);

        $adminPasswordUpdater->updateAdminPassword('newpassword');

        self::assertEquals('newpassword', $admin->getPassword());
    }

    public function getManager($object): Stub
    {
        $domainRepo = $this->createStub(DomainRepository::class);
        $domainRepo->method('findByName')->willReturn(null);

        $userRepo = $this->createStub(UserRepository::class);
        $userRepo->method('findByEmail')->willReturn($object);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnMap(
            [
                [Domain::class, $domainRepo],
                [User::class, $userRepo],
            ]);

        return $manager;
    }

    public function getUpdater(): PasswordUpdater
    {
        $hasher = new PlaintextPasswordHasher();
        $passwordHasherFactory = $this->createStub(PasswordHasherFactoryInterface::class);
        $passwordHasherFactory->method('getPasswordHasher')->willReturn($hasher);

        return new PasswordUpdater($passwordHasherFactory);
    }
}
