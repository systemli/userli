<?php

namespace App\Tests\Handler;

use App\Entity\User;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Handler\UserRestoreHandler;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class UserRestoreHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManagerInterface;
    private PasswordUpdater $passwordUpdater;
    private MailCryptKeyHandler $mailCryptKeyHandler;
    private RecoveryTokenHandler $recoveryTokenHandler;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->entityManagerInterface = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->entityManagerInterface->method('flush')->willReturn(true);

        $this->passwordUpdater = $this->getMockBuilder(PasswordUpdater::class)
            ->disableOriginalConstructor()->getMock();
        $this->passwordUpdater->method('updatePassword')->willReturnCallback(function (User $user) {
            $user->setPassword('new_password');
        });

        $this->mailCryptKeyHandler = $this->getMockBuilder(MailCryptKeyHandler::class)
            ->disableOriginalConstructor()->getMock();
        $this->mailCryptKeyHandler->method('create')->willReturnCallBack(function (User $user, string $password) {
            $user->setMailCryptSecretBox('MailCryptSecretBox');
            $user->setMailCryptEnabled(true);
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

    public function testRestoreUserWithoutMailCrypt(): void
    {
        $mailCryptEnv = 0;
        $handler = new UserRestoreHandler($this->entityManagerInterface, $this->passwordUpdater, $this->mailCryptKeyHandler, $this->recoveryTokenHandler, $this->eventDispatcher, $mailCryptEnv);

        $user = new User();
        $user->setDeleted(true);

        $recoveryToken = $handler->restoreUser($user, 'new_password');

        self::assertNull($recoveryToken);

        self::assertFalse($user->isDeleted());
        self::assertFalse($user->getMailCryptEnabled());
        self::assertNull($user->getMailCryptSecretBox());
        self::assertNull($user->getRecoverySecretBox());
        self::assertNotEmpty($user->getPassword());
    }

    public function testRestoreUserWithMailCrypt(): void
    {
        $mailCryptEnv = 2;
        $handler = new UserRestoreHandler($this->entityManagerInterface, $this->passwordUpdater, $this->mailCryptKeyHandler, $this->recoveryTokenHandler, $this->eventDispatcher, $mailCryptEnv);

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
