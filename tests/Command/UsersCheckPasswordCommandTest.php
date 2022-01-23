<?php

namespace App\Tests\Command;

use App\Command\UsersCheckPasswordCommand;
use App\Entity\User;
use App\Enum\Roles;
use App\Handler\MailCryptKeyHandler;
use App\Handler\UserAuthenticationHandler;
use App\Helper\FileDescriptorReader;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Tester\CommandTester;

class UsersCheckPasswordCommandTest extends TestCase
{
    protected $inputStream;
    protected $plainUser;
    protected $quotaUser;
    protected $mailCryptUser;
    protected $spamUser;

    public function setUp(): void
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
     */
    public function testExecuteInvalidArgumentException($inputStream, $exceptionMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $manager = $this->getManager();
        $reader = $this->getReaderFd3($inputStream);
        $handler = $this->getHandler();
        $mailCryptKeyHandler = $this->getMailCryptKeyHandler();
        $mailCrypt = 0;
        $mailUID = 5000;
        $mailGID = 5000;
        $mailLocation = 'var/vmail';

        $command = new UsersCheckPasswordCommand($manager,
                                            $reader,
                                            $handler,
                                            $mailCryptKeyHandler,
                                            $mailCrypt,
                                            $mailUID,
                                            $mailGID,
                                            $mailLocation);
        $commandTester = new CommandTester($command);

        $this->expectExceptionMessage($exceptionMessage);
        $commandTester->execute([]);
    }

    /**
     * @dataProvider validContentProvider
     */
    public function testExecuteFd3($inputStream, $returnCode): void
    {
        $manager = $this->getManager();
        $reader = $this->getReaderFd3($inputStream);
        $handler = $this->getHandler();
        $mailCryptKeyHandler = $this->getMailCryptKeyHandler();
        $mailCrypt = 2;
        $mailUID = 5000;
        $mailGID = 5000;
        $mailLocation = 'var/vmail';

        $command = new UsersCheckPasswordCommand($manager,
            $reader,
            $handler,
            $mailCryptKeyHandler,
            $mailCrypt,
            $mailUID,
            $mailGID,
            $mailLocation);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertEquals($returnCode, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider validContentProvider
     */
    public function testExecuteStdin($inputStream, $returnCode): void
    {
        $manager = $this->getManager();
        $reader = $this->getReaderStdin($inputStream);
        $handler = $this->getHandler();
        $mailCryptKeyHandler = $this->getMailCryptKeyHandler();
        $mailCrypt = 0;
        $mailUID = 5000;
        $mailGID = 5000;
        $mailLocation = 'var/vmail';

        $command = new UsersCheckPasswordCommand($manager,
            $reader,
            $handler,
            $mailCryptKeyHandler,
            $mailCrypt,
            $mailUID,
            $mailGID,
            $mailLocation);
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            [
                'checkpassword-reply' => ['/bin/true'],
            ]
        );

        self::assertEquals($returnCode, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider userDbContentProvider
     */
    public function testExecuteUserDbLookup($inputStream, $returnCode): void
    {
        $manager = $this->getManager();
        $reader = $this->getReaderFd3($inputStream);
        $handler = $this->getHandler();
        $mailCryptKeyHandler = $this->getMailCryptKeyHandler();
        $mailCrypt = 2;
        $mailUID = 5000;
        $mailGID = 5000;
        $mailLocation = 'var/vmail';

        putenv('AUTHORIZED=1');
        $command = new UsersCheckPasswordCommand($manager,
            $reader,
            $handler,
            $mailCryptKeyHandler,
            $mailCrypt,
            $mailUID,
            $mailGID,
            $mailLocation);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        self::assertEquals($returnCode, $commandTester->getStatusCode());
        putenv('AUTHORIZED');
    }

    public function invalidContentProvider(): array
    {
        $msgMissingEmail = 'Invalid input format: missing argument email. See https://cr.yp.to/checkpwd/interface.html for documentation of the checkpassword interface.';

        return [
            ['', $msgMissingEmail],
            ["\x00password", $msgMissingEmail],
            ["\x00password\x00", $msgMissingEmail],
            ["\x00password\x00timestamp\x00", $msgMissingEmail],
            ["\x00password\x00timestamp\x00extra", $msgMissingEmail],
        ];
    }

    public function validContentProvider(): array
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
            ['user@example.org', 1],
            ["user@example.org\x00", 1],
            ["user@example.org\x00\x00", 1],
            ["user@example.org\x00\x00\x00", 1],
            ["user@example.org\x00\x00timestamp\x00", 1],
            ["user@example.org\x00\x00timestamp\x00extra", 1],
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

    public function userDbContentProvider(): array
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
            ['spam@example.org', 0],
        ];
    }

    public function getManager(): ObjectManager
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

        $manager->method('getRepository')->willReturn($repository);

        return $manager;
    }

    public function getHandler(): UserAuthenticationHandler
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

    public function getMailCryptKeyHandler(): MailCryptKeyHandler
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

    public function getReaderStdin(string $inputStream): FileDescriptorReader
    {
        $reader = $this->getMockBuilder(FileDescriptorReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reader->method('readStdin')->willReturn($inputStream);

        return $reader;
    }

    public function getReaderFd3(string $inputStream): FileDescriptorReader
    {
        $reader = $this->getMockBuilder(FileDescriptorReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reader->method('readFd3')->willReturn($inputStream);

        return $reader;
    }
}
