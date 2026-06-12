<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Enum\MailCrypt;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Service\MailCryptCredentialRotation;
use App\Service\SettingsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MailCryptCredentialRotationTest extends TestCase
{
    public function testRotateReturnsNullWhenMailCryptBelowThreshold(): void
    {
        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturn(MailCrypt::DISABLED->value);

        $mailCryptKeyHandler = $this->createMock(MailCryptKeyHandler::class);
        $mailCryptKeyHandler->expects(self::never())->method('create');

        $recoveryTokenHandler = $this->createMock(RecoveryTokenHandler::class);
        $recoveryTokenHandler->expects(self::never())->method('create');

        $rotation = new MailCryptCredentialRotation($mailCryptKeyHandler, $recoveryTokenHandler, $settingsService);

        $result = $rotation->rotate(new User('user@example.org'), 'password');

        self::assertNull($result);
    }

    public function testRotateCallsMailCryptBeforeRecoveryTokenAndReturnsToken(): void
    {
        $user = new User('user@example.org');
        $user->setPlainRecoveryToken('expected-token');

        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturn(MailCrypt::ENABLED_ENFORCE_NEW_USERS->value);

        $callOrder = [];

        /** @var MailCryptKeyHandler&MockObject $mailCryptKeyHandler */
        $mailCryptKeyHandler = $this->createMock(MailCryptKeyHandler::class);
        $mailCryptKeyHandler->expects(self::once())
            ->method('create')
            ->with($user, 'password', true)
            ->willReturnCallback(static function () use (&$callOrder): void {
                $callOrder[] = 'mailCrypt';
            });

        /** @var RecoveryTokenHandler&MockObject $recoveryTokenHandler */
        $recoveryTokenHandler = $this->createMock(RecoveryTokenHandler::class);
        $recoveryTokenHandler->expects(self::once())
            ->method('create')
            ->with($user)
            ->willReturnCallback(static function () use (&$callOrder): void {
                $callOrder[] = 'recovery';
            });

        $rotation = new MailCryptCredentialRotation($mailCryptKeyHandler, $recoveryTokenHandler, $settingsService);

        $result = $rotation->rotate($user, 'password');

        self::assertSame('expected-token', $result);
        self::assertSame(['mailCrypt', 'recovery'], $callOrder, 'MailCrypt handler must run before RecoveryToken handler');
    }
}
