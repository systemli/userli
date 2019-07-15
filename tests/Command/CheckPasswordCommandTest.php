<?php

namespace App\Tests\Command;

use App\Command\CheckPasswordCommand;
use App\Entity\User;
use App\Enum\Roles;
use App\Handler\MailCryptKeyHandler;
use App\Handler\UserAuthenticationHandler;
use App\Helper\FileDescriptorReader;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CheckPasswordCommandTest extends TestCase
{
    protected $inputStream;
    protected $plainUser;
    protected $quotaUser;
    protected $mailCryptUser;
    protected $spamUser;

    public function setUp()
    {
        $this->plainUser = new User();
        $this->quotaUser = new User();
        $this->quotaUser->setQuota(1024);
        $this->mailCryptUser = new User();
        $this->mailCryptUser->setMailCrypt(true);
        $this->mailCryptUser->setMailCryptPublicKey('somePublicKey');
        $this->spamUser = new User();
        $this->spamUser->setRoles([Roles::SPAM]);
    }

    /**
     * @dataProvider      invalidContentProvider
     * @expectedException \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function testExecuteInvalidArgumentException($inputStream, $exceptionMessage)
    {
        $manager = $this->getManager();
        $reader = $this->getReaderFd3($inputStream);
        $handler = $this->getHandler();
        $mailCryptKeyHandler = $this->getMailCryptKeyHandler();
        $mailCryptEnabled = false;

        $command = new CheckPasswordCommand($manager, $reader, $handler, $mailCryptKeyHandler, $mailCryptEnabled);
        $commandTester = new CommandTester($command);

        $this->expectExceptionMessage($exceptionMessage);
        $commandTester->execute([]);
    }

    /**
     * @dataProvider validContentProvider
     */
    public function testExecuteFd3($inputStream, $returnCode)
    {
        $manager = $this->getManager();
        $reader = $this->getReaderFd3($inputStream);
        $handler = $this->getHandler();
        $mailCryptKeyHandler = $this->getMailCryptKeyHandler();
        $mailCryptEnabled = true;

        $command = new CheckPasswordCommand($manager, $reader, $handler, $mailCryptKeyHandler, $mailCryptEnabled);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertEquals($returnCode, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider validContentProvider
     */
    public function testExecuteStdin($inputStream, $returnCode)
    {
        $manager = $this->getManager();
        $reader = $this->getReaderStdin($inputStream);
        $handler = $this->getHandler();
        $mailCryptKeyHandler = $this->getMailCryptKeyHandler();
        $mailCryptEnabled = false;

        $command = new CheckPasswordCommand($manager, $reader, $handler, $mailCryptKeyHandler, $mailCryptEnabled);
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
    public function testExecuteUserDbLookup($inputStream, $returnCode)
    {
        $manager = $this->getManager();
        $reader = $this->getReaderFd3($inputStream);
        $handler = $this->getHandler();
        $mailCryptKeyHandler = $this->getMailCryptKeyHandler();
        $mailCryptEnabled = true;

        putenv('AUTHORIZED=1');
        $command = new CheckPasswordCommand($manager, $reader, $handler, $mailCryptKeyHandler, $mailCryptEnabled);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertEquals($returnCode, $commandTester->getStatusCode());
        putenv('AUTHORIZED');
    }

    public function invalidContentProvider()
    {
        $msgMissingEmail = 'Invalid input format: missing argument email. See https://cr.yp.to/checkpwd/interface.html for documentation of the checkpassword interface.';
        $msgMissingPassword = 'Invalid input format: missing argument password. See https://cr.yp.to/checkpwd/interface.html for documentation of the checkpassword interface.';

        return [
            ['', $msgMissingEmail],
            ['user@example.org', $msgMissingPassword],
            ["user@example.org\x00", $msgMissingPassword],
            ["user@example.org\x00\x00", $msgMissingPassword],
            ["user@example.org\x00\x00\x00", $msgMissingPassword],
            ["user@example.org\x00\x00timestamp\x00", $msgMissingPassword],
            ["user@example.org\x00\x00timestamp\x00extra", $msgMissingPassword],
            ["\x00password", $msgMissingEmail],
            ["\x00password\x00", $msgMissingEmail],
            ["\x00password\x00timestamp\x00", $msgMissingEmail],
            ["\x00password\x00timestamp\x00extra", $msgMissingEmail],
        ];
    }

    public function validContentProvider()
    {
        return [
            ["user@example.org\x00password", 0],
            ["user@example.org\x00password\x00", 0],
            ["user@example.org\x00password\x00\x00", 0],
            ["user@example.org\x00password\x00timestamp\x00", 0],
            ["user@example.org\x00password\x00timestamp\x00extra", 0],
            ["user@example.org\x00password\x00timestamp\x00extra\x00", 0],
            ["quota@example.org\x00password\x00\x00", 0],
            ["mailcrypt@example.org\x00password\x00\x00", 0],
            ["spam@example.org\x00password\x00\x00", 1],
            ["user@example.org\x00wrongpassword", 1],
            ["user@example.org\x00wrongpassword\x00", 1],
            ["user@example.org\x00wrongpassword\x00\x00", 1],
            ["unknown@example.org\x00password", 1],
            ["unknown@example.org\x00password\x00", 1],
            ["unknown@example.org\x00password\x00\x00", 1],
            ["unknown@example.org\x00password\x00timestamp\x00extra with \x00\x00", 1],
        ];
    }

    public function userDbContentProvider()
    {
        return [
            ['user@example.org', 0],
            ["user@example.org\x00", 0],
            ["user@example.org\x00\x00", 0],
            ["user@example.org\x00\x00\x00", 0],
            ["user@example.org\x00password\x00timestamp\x00extra", 0],
            ['quota@example.org', 0],
            ['mailcrypt@example.org', 0],
            ["unknown@example.org\x00password", 3],
            ['spam@example.org', 1],
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
                ['mailcrypt@example.org', $this->mailCryptUser],
                ['spam@example.org', $this->spamUser],
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
                [$this->mailCryptUser, 'password', $this->mailCryptUser],
                [$this->spamUser, 'password', $this->spamUser],
            ]
        );

        return $handler;
    }

    public function getMailCryptKeyHandler()
    {
        $mailCryptKeyHandler = $this->getMockBuilder(MailCryptKeyHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mailCryptKeyHandler->method('decrypt')->willReturnMap(
            [
                [$this->plainUser, 'password', ''],
                [$this->quotaUser, 'password', ''],
                [$this->mailCryptUser, 'password', 'somePrivateKey'],
                [$this->spamUser, 'password', ''],
            ]
        );

        return $mailCryptKeyHandler;
    }

    public function getReaderStdin(string $inputStream)
    {
        $reader = $this->getMockBuilder(FileDescriptorReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reader->method('readStdin')->willReturn($inputStream);

        return $reader;
    }

    public function getReaderFd3(string $inputStream)
    {
        $reader = $this->getMockBuilder(FileDescriptorReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reader->method('readFd3')->willReturn($inputStream);

        return $reader;
    }
}
