<?php

namespace App\Tests\Handler;

use App\Entity\User;
use App\Handler\UserAuthenticationHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class UserAuthenticationHandlerTest extends TestCase
{
    private $password = 'password';
    private $wrong = 'wrong';
    private $user;

    public function setUp(): void
    {
        $this->user = new User();
        $this->user->setPassword($this->password);
    }

    protected function createHandler(): UserAuthenticationHandler
    {
        $encoder = $this->getMockBuilder(PasswordEncoderInterface::class)->getMock();
        $encoder->method('isPasswordValid')->willReturnMap(
            [
                [$this->user->getPassword(), $this->password, $this->user->getSalt(), true],
                [$this->user->getPassword(), $this->wrong, $this->user->getSalt(), false],
            ]
        );

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $encoderFactory = $this->getMockBuilder(EncoderFactory::class)
            ->disableOriginalConstructor()->getMock();
        $encoderFactory->method('getEncoder')
            ->with(self::equalTo($this->user))
            ->willReturn($encoder);

        return new UserAuthenticationHandler($encoderFactory, $eventDispatcher);
    }

    public function testAuthenticate(): void
    {
        $handler = $this->createHandler();

        self::assertEquals($this->user, $handler->authenticate($this->user, $this->password));
        self::assertEquals(null, $handler->authenticate($this->user, $this->wrong));
    }
}
