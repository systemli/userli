<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Entity\MailAppPassword;
use App\Entity\User;
use App\Handler\UserAuthenticationHandler;
use App\Service\MailAppPasswordManager;
use App\Service\UserLastLoginUpdateService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class UserAuthenticationHandlerTest extends TestCase
{
    private string $password = 'password';
    private string $wrong = 'wrong';
    private string $mailAppPassword = 'mail-app-password';
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User('test@example.org');
        $this->user->setPassword($this->password);
    }

    protected function createHandler(
        ?EventDispatcherInterface $eventDispatcher = null,
        ?UserLastLoginUpdateService $lastLoginUpdateService = null,
    ): UserAuthenticationHandler {
        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturnCallback(
            fn (string $hash, string $plain) => $plain === $this->password
        );

        $passwordHasherFactory = $this->createStub(PasswordHasherFactoryInterface::class);
        $passwordHasherFactory->method('getPasswordHasher')
            ->willReturn($hasher);

        $mailAppPasswordManager = $this->createStub(MailAppPasswordManager::class);
        $mailAppPasswordManager->method('find')->willReturnCallback(
            fn (User $user, string $plain) => $plain === $this->mailAppPassword
                ? new MailAppPassword($user, 'Phone', hash('sha256', $plain), 'box')
                : null
        );

        return new UserAuthenticationHandler(
            $passwordHasherFactory,
            $eventDispatcher ?? $this->createStub(EventDispatcherInterface::class),
            $mailAppPasswordManager,
            $lastLoginUpdateService ?? $this->createStub(UserLastLoginUpdateService::class),
        );
    }

    public function testAuthenticate(): void
    {
        $handler = $this->createHandler();

        self::assertEquals($this->user, $handler->authenticate($this->user, $this->password));
        self::assertNull($handler->authenticate($this->user, $this->wrong));
    }

    public function testMailAppPasswordRejectedByDefault(): void
    {
        $handler = $this->createHandler();

        self::assertNull($handler->authenticate($this->user, $this->mailAppPassword));
    }

    public function testMailAppPasswordAcceptedWhenAllowed(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');
        $lastLoginUpdateService = $this->createMock(UserLastLoginUpdateService::class);
        $lastLoginUpdateService->expects($this->once())->method('updateLastLogin')->with($this->user);

        $handler = $this->createHandler($eventDispatcher, $lastLoginUpdateService);

        self::assertSame($this->user, $handler->authenticate($this->user, $this->mailAppPassword, true));
    }

    public function testMainPasswordStillWorksWhenMailAppPasswordAllowed(): void
    {
        $handler = $this->createHandler();

        self::assertSame($this->user, $handler->authenticate($this->user, $this->password, true));
        self::assertNull($handler->authenticate($this->user, $this->wrong, true));
    }
}
