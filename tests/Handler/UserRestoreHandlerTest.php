<?php

namespace App\Tests\Handler;

use App\Entity\User;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Handler\UserRestoreHandler;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UserRestoreHandlerTest extends TestCase
{
    protected function createHandler(): UserRestoreHandler
    {
        $passwordUpdater = $this->getMockBuilder(PasswordUpdater::class)
            ->disableOriginalConstructor()->getMock();
        $passwordUpdater->method('updatePassword')->willReturnCallback(function (User $user) {
            $user->setPassword('new_password');
        });

        $entityManagerInterface = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()->getMock();
        $entityManagerInterface->method('flush')->willReturn(true);

        $mailCryptKeyHandler = $this->getMockBuilder(MailCryptKeyHandler::class)
            ->disableOriginalConstructor()->getMock();
        $mailCryptKeyHandler->method('create')->willReturnCallBack(function (User $user, string $password) {
            $user->setMailCryptSecretBox('MailCryptSecretBox');
        });

        $recoveryTokenHandler = $this->getMockBuilder(RecoveryTokenHandler::class)
            ->disableOriginalConstructor()->getMock();
        $recoveryTokenHandler->method('create')->willReturnCallBack(function (User $user) {
            $user->setRecoverySecretBox('RecoverySecretBox');
            $user->setPlainRecoveryToken('PlainRecoveryToken');
        });

        $mailCryptEnv = 2;

        return new UserRestoreHandler($entityManagerInterface, $passwordUpdater, $mailCryptKeyHandler, $recoveryTokenHandler, $mailCryptEnv);
    }

    public function testRestoreUserWithMailCrypt(): void
    {
        $handler = $this->createHandler();

        $user = new User();
        $user->setDeleted(true);

        $recoveryToken = $handler->restoreUser($user, 'new_password');

        self::assertEquals('PlainRecoveryToken', $recoveryToken);

        self::assertFalse($user->isDeleted());
        self::assertTrue($user->getMailCryptEnabled());
        self::assertEquals('MailCryptSecretBox', $user->getMailCryptSecretBox());
        self::assertNotEmpty('RecoverySecretBox', $user->getRecoverySecretBox());
        self::assertNotEmpty($user->getPassword());
    }
}
