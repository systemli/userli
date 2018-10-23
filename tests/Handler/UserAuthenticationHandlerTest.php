<?php

namespace App\Tests\Handler;

use App\Entity\User;
use App\Handler\UserAuthenticationHandler;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class UserAuthenticationHandlerTest extends TestCase
{
    private $password = 'password';
    private $wrong = 'wrong';
    private $user;

    public function setUp()
    {
        $this->user = new User();
        $this->user->setPassword($this->password);
    }

    protected function createHandler()
    {
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()->getMock();
        $objectManager->expects($this->any())->method('flush')->willReturn(true);

        $encoder = $this->getMockBuilder(PasswordEncoderInterface::class)->getMock();
        $encoder->expects($this->any())->method('isPasswordValid')->willReturnMap(
            [
                [$this->user->getPassword(), $this->password, $this->user->getSalt(), true],
                [$this->user->getPassword(), $this->wrong, $this->user->getSalt(), false],
            ]
        );

        $encoderFactory = $this->getMockBuilder(EncoderFactory::class)
            ->disableOriginalConstructor()->getMock();
        $encoderFactory->expects($this->any())->method('getEncoder')
            ->with($this->equalTo($this->user))
            ->will($this->returnValue($encoder));

        return new UserAuthenticationHandler($objectManager, $encoderFactory);
    }

    public function testAuthenticate()
    {
        $handler = $this->createHandler();

        self::assertEquals($this->user, $handler->authenticate($this->user, $this->password));
        self::assertEquals(null, $handler->authenticate($this->user, $this->wrong));
    }
}
