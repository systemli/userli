<?php

namespace App\Tests\Command;

use App\Command\CheckPasswordCommand;
use App\Entity\User;
use App\Handler\UserAuthenticationHandler;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CheckPasswordCommandTest extends TestCase
{
    public function testExecuteDefaultFile()
    {
        $manager = $this->getManager();

        $handler = $this->getMockBuilder(UserAuthenticationHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $handler->method('authenticate')->willReturnMap(
            [
                ['name', new User()],
                ['invalid', null],
            ]
        );

        $command = new CheckPasswordCommand($manager, $handler);
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            [
                'checkpassword-reply' => ['/bin/true'],
            ]
        );

        $output = $commandTester->getDisplay();

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function getManager()
    {
        $manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->method('findByEmail')->willReturnMap(
            [
                ['new', null],
                ['name', true],
            ]
        );

        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        return $manager;
    }
}
