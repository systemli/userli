<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Entity\User;
use App\Handler\UserAuthenticationHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class UserAuthenticationHandlerTest extends TestCase
{
    private string $password = 'password';
    private string $wrong = 'wrong';
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->user->setPassword($this->password);
    }

    protected function createHandler(): UserAuthenticationHandler
    {
        $hasher = $this->getMockBuilder(PasswordHasherInterface::class)->getMock();
        $hasher->method('verify')->willReturnMap(
            [
                [$this->user->getPassword(), $this->password, true],
                [$this->user->getPassword(), $this->wrong, false],
            ]
        );

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $passwordHasherFactory = $this->getMockBuilder(PasswordHasherFactoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $passwordHasherFactory->method('getPasswordHasher')
            ->with(self::equalTo($this->user))
            ->willReturn($hasher);

        return new UserAuthenticationHandler($passwordHasherFactory, $eventDispatcher);
    }

    public function testAuthenticate(): void
    {
        $handler = $this->createHandler();

        self::assertEquals($this->user, $handler->authenticate($this->user, $this->password));
        self::assertNull($handler->authenticate($this->user, $this->wrong));
    }
}
