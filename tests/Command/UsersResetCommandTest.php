<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UsersResetCommand;
use App\Entity\User;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Helper\PasswordUpdater;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class UsersResetCommandTest extends TestCase
{
    private UsersResetCommand $command;
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User('user@example.org');
        $this->user->setTotpSecret('secret');
        $this->user->setTotpConfirmed(true);
        $this->user->setTotpBackupCodes(['123456']);

        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->method('findByEmail')
            ->willReturn($this->user);

        $manager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $manager->method('getRepository')->willReturn($repository);

        $passwordUpdater = $this->getMockBuilder(PasswordUpdater::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailCryptKeyHandler = $this->getMockBuilder(MailCryptKeyHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $recoveryTokenHandler = $this->getMockBuilder(RecoveryTokenHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailLocation = '/tmp/vmail';

        $this->command = new UsersResetCommand($manager, $passwordUpdater, $mailCryptKeyHandler, $recoveryTokenHandler, $mailLocation);
    }

    public function testExecute(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:reset');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes', 'longtestpassword1234', 'longtestpassword1234']);

        // Test dry run
        $commandTester->execute(['--user' => 'user@example.org', '--dry-run' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Would reset user user@example.org', $output);

        // Test real run
        $commandTester->execute(['--user' => 'user@example.org']);

        // Verify that user properties got reset
        self::assertFalse($this->user->getTotpConfirmed());
        self::assertEmpty($this->user->getTotpBackupCodes());
        self::assertFalse($this->user->isTotpAuthenticationEnabled());

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Resetting user user@example.org', $output);
    }

    public function testExecuteShortPassword(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The password doesn't comply with our security policy.");

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:reset');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes', 'short', 'short', 'short', 'short', 'short']);

        $commandTester->execute(['--user' => 'user@example.org']);
    }

    public function testExecutePasswordsDontMatch(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The passwords don't match");

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:reset');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes', 'longtestpassword1234', 'different']);

        $commandTester->execute(['--user' => 'user@example.org']);
    }

    public function testExecuteWithoutUser(): void
    {
        $this->expectException(UserNotFoundException::class);

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:reset');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
    }
}
