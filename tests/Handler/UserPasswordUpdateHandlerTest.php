<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Entity\User;
use App\Enum\MailCrypt;
use App\Handler\MailCryptKeyHandler;
use App\Handler\UserPasswordUpdateHandler;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserPasswordUpdateHandlerTest extends TestCase
{
    private UserPasswordUpdateHandler $handler;
    private MockObject $manager;
    private MockObject $passwordUpdater;
    private MockObject $mailCryptKeyHandler;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(EntityManagerInterface::class);
        $this->passwordUpdater = $this->createMock(PasswordUpdater::class);
        $this->mailCryptKeyHandler = $this->createMock(MailCryptKeyHandler::class);

        $this->handler = new UserPasswordUpdateHandler(
            $this->manager,
            $this->passwordUpdater,
            $this->mailCryptKeyHandler,
            2 // Assuming MailCrypt::ENABLED_ENFORCE_NEW_USERS is 2
        );
    }

    public function testUpdatePasswordSuccessfully(): void
    {
        $newPassword = 'newSecurePassword123';
        $oldPassword = 'oldPassword123';

        // Mock user without mail crypt secret box
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('hasMailCryptSecretBox')
            ->willReturn(false);

        // Expect password update to be called
        $this->passwordUpdater
            ->expects($this->once())
            ->method('updatePassword')
            ->with($user, $newPassword);

        // Mail crypt key handler should NOT be called
        $this->mailCryptKeyHandler
            ->expects($this->never())
            ->method('update');

        // Entity manager should flush
        $this->manager
            ->expects($this->once())
            ->method('flush');

        $this->handler->updatePassword($user, $newPassword, $oldPassword);
    }

    public function testUpdatePasswordWithMailCryptKey(): void
    {
        $newPassword = 'newSecurePassword123';
        $oldPassword = 'oldPassword123';

        // Mock user with mail crypt secret box
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('hasMailCryptSecretBox')
            ->willReturn(true);

        // Expect password update to be called
        $this->passwordUpdater
            ->expects($this->once())
            ->method('updatePassword')
            ->with($user, $newPassword);

        // Mail crypt key handler SHOULD be called
        $this->mailCryptKeyHandler
            ->expects($this->once())
            ->method('update')
            ->with($user, $oldPassword, $newPassword);

        // Entity manager should flush
        $this->manager
            ->expects($this->once())
            ->method('flush');

        $this->handler->updatePassword($user, $newPassword, $oldPassword);
    }

    public function testUpdatePasswordFailsWhenPasswordUpdaterThrows(): void
    {
        $newPassword = 'newSecurePassword123';
        $oldPassword = 'oldPassword123';

        $user = $this->createMock(User::class);
        $user->expects($this->never())
            ->method('hasMailCryptSecretBox');

        // Password updater throws exception
        $this->passwordUpdater
            ->expects($this->once())
            ->method('updatePassword')
            ->with($user, $newPassword)
            ->willThrowException(new \Exception('Password update failed'));

        // Mail crypt key handler should NOT be called
        $this->mailCryptKeyHandler
            ->expects($this->never())
            ->method('update');

        // Entity manager should NOT flush
        $this->manager
            ->expects($this->never())
            ->method('flush');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Password update failed');

        $this->handler->updatePassword($user, $newPassword, $oldPassword);
    }

    public function testUpdatePasswordFailsWhenMailCryptKeyHandlerThrows(): void
    {
        $newPassword = 'newSecurePassword123';
        $oldPassword = 'oldPassword123';

        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('hasMailCryptSecretBox')
            ->willReturn(true);

        // Password updater succeeds
        $this->passwordUpdater
            ->expects($this->once())
            ->method('updatePassword')
            ->with($user, $newPassword);

        // Mail crypt key handler throws exception
        $this->mailCryptKeyHandler
            ->expects($this->once())
            ->method('update')
            ->with($user, $oldPassword, $newPassword)
            ->willThrowException(new \Exception('Mail crypt key update failed'));

        // Entity manager should NOT flush
        $this->manager
            ->expects($this->never())
            ->method('flush');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Mail crypt key update failed');

        $this->handler->updatePassword($user, $newPassword, $oldPassword);
    }

    public function testUpdatePasswordCallsMethodsInCorrectOrder(): void
    {
        $newPassword = 'newSecurePassword123';
        $oldPassword = 'oldPassword123';

        $user = $this->createMock(User::class);

        // Set up expectations for method call order
        $user->expects($this->once())
            ->method('hasMailCryptSecretBox')
            ->willReturn(true);

        // Create a sequence to verify call order
        $this->passwordUpdater
            ->expects($this->once())
            ->method('updatePassword')
            ->with($user, $newPassword);

        $this->mailCryptKeyHandler
            ->expects($this->once())
            ->method('update')
            ->with($user, $oldPassword, $newPassword);

        $this->manager
            ->expects($this->once())
            ->method('flush');

        $this->handler->updatePassword($user, $newPassword, $oldPassword);
    }

    /**
     * @dataProvider passwordDataProvider
     */
    public function testUpdatePasswordWithVariousPasswordFormats(
        string $newPassword,
        string $oldPassword
    ): void {
        $user = $this->createMock(User::class);
        $user->method('hasMailCryptSecretBox')->willReturn(false);

        $this->passwordUpdater
            ->expects($this->once())
            ->method('updatePassword')
            ->with($user, $newPassword);

        $this->manager
            ->expects($this->once())
            ->method('flush');

        $this->handler->updatePassword($user, $newPassword, $oldPassword);
    }

    public function passwordDataProvider(): array
    {
        return [
            'simple passwords' => ['newPass123', 'oldPass123'],
            'special characters' => ['N3w!P@ssw0rd$', 'Old!P@ssw0rd$'],
            'unicode characters' => ['newPäßwörd123', 'oldPäßwörd123'],
            'long passwords' => [
                'ThisIsAVeryLongPasswordWithManyCharacters123!',
                'ThisWasAnOldVeryLongPasswordWithManyCharacters456!'
            ],
            'empty old password' => ['newPassword123', ''],
        ];
    }
}
