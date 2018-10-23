<?php

namespace App\Tests\Command;

use App\Command\CheckPasswordCommand;
use App\Entity\User;
use App\Handler\UserAuthenticationHandler;
use App\Helper\FileDescriptorReader;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CheckPasswordCommandTest extends TestCase
{
    public function testExecuteTest()
    {
        $userUser = new User();
        $userInvalid = new User();

        $manager = $this->getManager();

        $content = "user@example.org\x00password\x00\x00";

        $reader = $this->getMockBuilder(FileDescriptorReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reader->method('readStdin')->willReturn($content);

        $handler = $this->getMockBuilder(UserAuthenticationHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $handler->method('authenticate')->willReturnMap(
            [
                [$userUser, new User()],
                [$userInvalid, null],
            ]
        );

        $command = new CheckPasswordCommand($manager, $reader, $handler);
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
                ['new@example.org', null],
                ['user@example.org', true],
            ]
        );

        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        return $manager;
    }
}
