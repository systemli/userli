<?php

namespace App\Tests\Handler;

use App\Entity\User;
use App\Handler\RecoveryTokenHandler;
use App\Handler\UserPasswordUpdateHandler;
use App\Handler\UserRestoreHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class UserRestoreHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManagerInterface;
    private UserPasswordUpdateHandler $userPasswordUpdateHandler;
    private RecoveryTokenHandler $recoveryTokenHandler;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->entityManagerInterface = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->entityManagerInterface->method('flush')->willReturn(true);

        $this->userPasswordUpdateHandler = $this->getMockBuilder(UserPasswordUpdateHandler::class)
            ->disableOriginalConstructor()->getMock();
        $this->userPasswordUpdateHandler->method('updatePassword')->willReturnCallback(function (User $user) {
            $user->setPassword('new_password');
        });

        $this->recoveryTokenHandler = $this->getMockBuilder(RecoveryTokenHandler::class)
            ->disableOriginalConstructor()->getMock();
        $this->recoveryTokenHandler->method('create')->willReturnCallBack(function (User $user) {
            $user->setRecoverySecretBox('RecoverySecretBox');
            $user->setPlainRecoveryToken('PlainRecoveryToken');
        });

        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()->getMock();
    }

    public function testRestoreUserWithMailCrypt(): void
    {
        $mailCryptEnv = 2;
        $handler = new UserRestoreHandler($this->entityManagerInterface, $this->userPasswordUpdateHandler, $this->recoveryTokenHandler, $this->eventDispatcher, $mailCryptEnv);

        $user = new User();
        $user->setDeleted(true);

        $recoveryToken = $handler->restoreUser($user, 'new_password');

        self::assertEquals('PlainRecoveryToken', $recoveryToken);
        self::assertFalse($user->isDeleted());
    }
}
