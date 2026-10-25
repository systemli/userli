<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Entity\AppPassword;
use App\Entity\User;
use App\Handler\UserAuthenticationHandler;
use App\Service\AppPasswordManager;
use App\Service\SettingsService;
use App\Service\UserLastLoginUpdateService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class UserAuthenticationHandlerTest extends TestCase
{
    private string $password = 'password';
    private string $wrong = 'wrong';
    private string $appPassword = 'app-password';
    private User $user;
    private AppPassword $appPasswordEntity;

    protected function setUp(): void
    {
        $this->user = new User('test@example.org');
        $this->user->setPassword($this->password);
        $this->appPasswordEntity = new AppPassword($this->user, 'Phone', hash('sha256', $this->appPassword), 'box');
    }

    protected function createHandler(
        ?EventDispatcherInterface $eventDispatcher = null,
        ?UserLastLoginUpdateService $lastLoginUpdateService = null,
        bool $appPasswordsRequired = true,
    ): UserAuthenticationHandler {
        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturnCallback(
            fn (string $hash, string $plain) => $plain === $this->password
        );

        $passwordHasherFactory = $this->createStub(PasswordHasherFactoryInterface::class);
        $passwordHasherFactory->method('getPasswordHasher')
            ->willReturn($hasher);

        $appPasswordManager = $this->createStub(AppPasswordManager::class);
        $appPasswordManager->method('find')->willReturnCallback(
            fn (User $user, string $plain) => $plain === $this->appPassword ? $this->appPasswordEntity : null
        );

        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturnMap([['twofactor_app_passwords_required', null, $appPasswordsRequired]]);

        return new UserAuthenticationHandler(
            $passwordHasherFactory,
            $eventDispatcher ?? $this->createStub(EventDispatcherInterface::class),
            $appPasswordManager,
            $lastLoginUpdateService ?? $this->createStub(UserLastLoginUpdateService::class),
            $settingsService,
        );
    }

    public function testAuthenticate(): void
    {
        $handler = $this->createHandler();

        self::assertEquals($this->user, $handler->authenticate($this->user, $this->password));
        self::assertNull($handler->authenticate($this->user, $this->wrong));
    }

    public function testAppPasswordRejectedByAuthenticate(): void
    {
        $handler = $this->createHandler();

        self::assertNull($handler->authenticate($this->user, $this->appPassword));
    }

    public function testMailClientWithAppPasswordReturnsAppPassword(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');
        $lastLoginUpdateService = $this->createMock(UserLastLoginUpdateService::class);
        $lastLoginUpdateService->expects($this->once())->method('updateLastLogin')->with($this->user, $this->appPasswordEntity);

        $handler = $this->createHandler($eventDispatcher, $lastLoginUpdateService);

        self::assertSame($this->appPasswordEntity, $handler->authenticateMailClient($this->user, $this->appPassword));
    }

    public function testMailClientWithMainPasswordReturnsUser(): void
    {
        $handler = $this->createHandler();

        self::assertSame($this->user, $handler->authenticateMailClient($this->user, $this->password));
        self::assertNull($handler->authenticateMailClient($this->user, $this->wrong));
    }

    public function testMailClientWithMainPasswordLeavesLastLoginToLoginEvent(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method('dispatch');
        $lastLoginUpdateService = $this->createMock(UserLastLoginUpdateService::class);
        $lastLoginUpdateService->expects($this->never())->method('updateLastLogin');

        $handler = $this->createHandler($eventDispatcher, $lastLoginUpdateService);

        self::assertSame($this->user, $handler->authenticateMailClient($this->user, $this->password));
    }

    public function testMainPasswordRejectedForMailClientsOfTwofactorUsers(): void
    {
        $this->user->setTotpSecret('secret');
        $this->user->setTotpConfirmed(true);
        $handler = $this->createHandler();

        self::assertNull($handler->authenticateMailClient($this->user, $this->password));
        self::assertSame($this->appPasswordEntity, $handler->authenticateMailClient($this->user, $this->appPassword));
        self::assertSame($this->user, $handler->authenticate($this->user, $this->password));
    }

    public function testMainPasswordAcceptedForTwofactorUsersWhenNotRequired(): void
    {
        $this->user->setTotpSecret('secret');
        $this->user->setTotpConfirmed(true);
        $handler = $this->createHandler(appPasswordsRequired: false);

        self::assertSame($this->user, $handler->authenticateMailClient($this->user, $this->password));
    }
}
