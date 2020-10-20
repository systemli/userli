<?php

namespace App\Tests\Handler;

use App\Entity\Alias;
use App\Entity\User;
use App\Handler\DeleteHandler;
use App\Handler\WkdHandler;
use App\Helper\PasswordUpdater;
use App\Repository\AliasRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

class DeleteHandlerTest extends TestCase
{
    protected function createHandler(): DeleteHandler
    {
        $passwordUpdater = $this->getMockBuilder(PasswordUpdater::class)
            ->disableOriginalConstructor()->getMock();
        $passwordUpdater->method('updatePassword')->willReturnCallback(function (User $user) {
            $user->setPassword('new_password');
        });

        $aliasRepositry = $this->getMockBuilder(AliasRepository::class)
            ->disableOriginalConstructor()->getMock();
        $aliasRepositry->method('findByUser')->willReturn([]);

        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()->getMock();
        $objectManager->method('getRepository')->willReturn($aliasRepositry);
        $objectManager->method('flush')->willReturn(true);

        $wkdHandler = $this->getMockBuilder(WkdHandler::class)
            ->disableOriginalConstructor()->getMock();

        return new DeleteHandler($passwordUpdater, $objectManager, $wkdHandler);
    }

    public function testDeleteAlias(): void
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
        self::assertEquals(null, $alias->getDestination());
    }

    public function testDeleteUser(): void
    {
        $handler = $this->createHandler();

        $oldPassword = 'old_password';

        $user = new User();
        $user->setPassword($oldPassword);
        $user->setEmail('alice@example.org');

        $handler->deleteUser($user);

        self::assertTrue($user->isDeleted());
        self::assertNotEquals($oldPassword, $user->getPassword());
    }
}
