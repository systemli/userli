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
    protected $content;
    protected $plainUser;
    protected $quotaUser;

    public function setUp()
    {
        $this->plainUser = new User();
        $this->quotaUser = new User();
        $this->quotaUser->setQuota(1024);
    }

    /**
     * @dataProvider      invalidContentProvider
     * @expectedException \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function testExecuteInvalidArgumentException($content)
    {
        $manager = $this->getManager();
        $reader = $this->getReaderFd3($content);
        $handler = $this->getHandler();

        $command = new CheckPasswordCommand($manager, $reader, $handler);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
    }

    /**
     * @expectedException \Exception
     */
    public function testExecuteProcessException()
    {
        $manager = $this->getManager();
        $reader = $this->getReaderFd3("user@example.org\x00password\x00\x00");
        $handler = $this->getHandler();

        $command = new CheckPasswordCommand($manager, $reader, $handler);
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            [
                'checkpassword-reply' => ['/usr/local/bin/nonexistent'],
            ]
        );
    }

    /**
     * @dataProvider validContentProvider
     */
    public function testExecuteFd3($content, $returnCode)
    {
        $manager = $this->getManager();
        $reader = $this->getReaderFd3($content);
        $handler = $this->getHandler();

        $command = new CheckPasswordCommand($manager, $reader, $handler);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertEquals($returnCode, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider validContentProvider
     */
    public function testExecuteStdin($content, $returnCode)
    {
        $manager = $this->getManager();
        $reader = $this->getReaderStdin($content);
        $handler = $this->getHandler();

        $command = new CheckPasswordCommand($manager, $reader, $handler);
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            [
                'checkpassword-reply' => ['/bin/true'],
            ]
        );

        $this->assertEquals($returnCode, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider userDbContentProvider
     */
    public function testExecuteUserDbLookup($content, $returnCode)
    {
        $manager = $this->getManager();
        $reader = $this->getReaderFd3($content);
        $handler = $this->getHandler();

        putenv('AUTHORIZED=1');
        $command = new CheckPasswordCommand($manager, $reader, $handler);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertEquals($returnCode, $commandTester->getStatusCode());
        putenv('AUTHORIZED');
    }

    public function invalidContentProvider()
    {
        return [
            ['user@example.org password timestamp extra'],
            ["user@example.org\x00password timestamp extra"],
            ["user@example.org\x00password\x00timestamp extra"],
            ["user@example.org\x00password\x00timestamp\x01extra"],
            ["user@example.org\x00password\x01timestamp\x00extra"],
            ["user@example.org\x01password\x00timestamp\x00extra"],
            ["\x00password\x00timestamp\x00extra"],
            //["user@example.org\x00\x00timestamp\x00extra"], <- this is an empty password and not invalid
        ];
    }

    public function validContentProvider()
    {
        return [
            ["user@example.org\x00password\x00\x00", 0],
            ["user@example.org\x00password\x00timestamp\x00", 0],
            ["user@example.org\x00password\x00timestamp\x00extra", 0],
            ["user@example.org\x00password\x00timestamp\x00extra\x00", 0],
            ["quota@example.org\x00password\x00\x00", 0],
            ["user@example.org\x00wrongpassword\x00\x00", 1],
            ["unknown@example.org\x00password\x00\x00", 1],
            ["unknown@example.org\x00password\x00timestamp\x00extra with \x00\x00", 1],
        ];
    }

    public function userDbContentProvider()
    {
        return [
            ["user@example.org\x00password\x00\x00", 0],
            ["unknown@example.org\x00password\x00\x00", 3],
        ];
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
                ['user@example.org', $this->plainUser],
                ['quota@example.org', $this->quotaUser],
            ]
        );

        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        return $manager;
    }

    public function getHandler()
    {
        $handler = $this->getMockBuilder(UserAuthenticationHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $handler->method('authenticate')->willReturnMap(
            [
                [$this->plainUser, 'password', $this->plainUser],
                [$this->quotaUser, 'password', $this->quotaUser],
            ]
        );

        return $handler;
    }

    public function getReaderStdin(string $content)
    {
        $reader = $this->getMockBuilder(FileDescriptorReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reader->method('readStdin')->willReturn($content);

        return $reader;
    }

    public function getReaderFd3(string $content)
    {
        $reader = $this->getMockBuilder(FileDescriptorReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reader->method('readFd3')->willReturn($content);

        return $reader;
    }
}
