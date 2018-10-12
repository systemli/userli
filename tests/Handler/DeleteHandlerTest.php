<?php

namespace App\Tests\Handler;

use App\Entity\Alias;
use App\Entity\User;
use App\Handler\DeleteHandler;
use App\Helper\PasswordUpdater;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

class DeleteHandlerTest extends TestCase
{
    protected function createHandler()
    {
        $passwordUpdater = $this->getMockBuilder(PasswordUpdater::class)
            ->disableOriginalConstructor()->getMock();
        $passwordUpdater->expects($this->any())->method('updatePassword')->willReturnCallback(function (User $user) {
            $user->setPassword('new_password');
        });

        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()->getMock();
        $objectManager->expects($this->any())->method('flush')->willReturn(true);

        return new DeleteHandler($passwordUpdater, $objectManager);


    }

    public function testDeleteAlias()
    {
        $handler = $this->createHandler();

        $user = new User();
        $alias = new Alias();
        $alias->setUser($user);

        $user2 = new User();
        $handler->deleteAlias($alias, $user2);

        self::assertNotTrue($alias->isDeleted());
        self::assertEquals($alias->getUser(), $user);

        $handler->deleteAlias($alias);

        self::assertTrue($alias->isDeleted());
        self::assertNotEquals($alias->getUser(), $user);
    }

    public function testDeleteUser()
    {
        $handler = $this->createHandler();

        $oldPassword = 'old_password';

        $user = new User();
        $user->setPassword($oldPassword);

        $handler->deleteUser($user);

        self::assertTrue($user->isDeleted());
        self::assertNotEquals($oldPassword, $user->getPassword());
    }
}
