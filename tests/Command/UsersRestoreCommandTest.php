<?php

namespace App\Tests\Command;

use App\Command\UsersRestoreCommand;
use Exception;
use App\Entity\User;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Helper\PasswordUpdater;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class UsersRestoreCommandTest extends TestCase
{
    private UsersRestoreCommand $command;
    private User $user;

    public function setUp(): void
    {
        $this->user = new User();
        $this->user->setEmail('deleted@example.org');
        $this->user->setDeleted(true);

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

        $mailCryptEnv = 3;

        $this->command = new UsersRestoreCommand($manager, $passwordUpdater, $mailCryptKeyHandler, $recoveryTokenHandler, $mailCryptEnv);
    }

    public function testExecute(): void
    {
        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:users:restore');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['longtestpassword1234', 'longtestpassword1234']);

        // Test dry run
        $commandTester->execute(['--user' => 'deleted@example.org', '--dry-run' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Would restore user deleted@example.org', $output);

        // Test real run
        $commandTester->execute(['--user' => 'deleted@example.org']);

        // Verify that user properties got restore
        self::assertFalse($this->user->isDeleted());
        self::assertTrue($this->user->getMailCrypt());

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Restoring user deleted@example.org', $output);
    }

    public function testExecuteShortPassword(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The password doesn't comply with our security policy.");

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:users:restore');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['short', 'short', 'short', 'short', 'short']);

        $commandTester->execute(['--user' => 'deleted@example.org']);
    }

    public function testExecutePasswordsDontMatch(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The passwords don't match");

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:users:restore');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes', 'longtestpassword1234', 'different']);

        $commandTester->execute(['--user' => 'deleted@example.org']);
    }

    public function testExecuteWithoutUser(): void
    {
        $this->expectException(UserNotFoundException::class);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:users:restore');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
    }
}
