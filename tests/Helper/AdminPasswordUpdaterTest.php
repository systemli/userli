<?php

namespace App\Tests\Helper;

use App\Entity\Domain;
use App\Entity\User;
use App\Handler\UserPasswordUpdateHandler;
use App\Helper\AdminPasswordUpdater;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AdminPasswordUpdaterTest extends TestCase
{
    public function testUpdateAdminPassword(): void
    {
        $admin = new User();
        $admin->setPassword('impossible_login');

        $handler = $this->getMockBuilder(UserPasswordUpdateHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $handler->method('updatePassword')
            ->willReturnCallback(function (User $user, string $newPassword) {
                $user->setPassword($newPassword);
            });

        $updater = new AdminPasswordUpdater($this->getManager($admin), $handler);

        $updater->updateAdminPassword('newpassword');

        self::assertEquals('newpassword', $admin->getPassword());
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
}
