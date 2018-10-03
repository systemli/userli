<?php

namespace App\Tests\Handler;

use App\Entity\User;
use App\Handler\DeleteHandler;
use App\Helper\PasswordUpdater;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

class DeleteHandlerTest extends TestCase
{
    public function testDeleteUser()
    {
        $passwordUpdater = $this->getMockBuilder(PasswordUpdater::class)
            ->disableOriginalConstructor()->getMock();
        $passwordUpdater->expects($this->any())->method('updatePassword')->willReturnCallback(function (User $user) {
            $user->setPassword('new_password');
        });

        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()->getMock();
        $objectManager->expects($this->any())->method('flush')->willReturn(true);

        $handler = new DeleteHandler($passwordUpdater, $objectManager);

        $oldPassword = 'old_password';

        $user = new User();
        $user->setPassword($oldPassword);

        $handler->deleteUser($user);

        self::assertTrue($user->isDeleted());
        self::assertNotEquals($oldPassword, $user->getPassword());
    }
}
