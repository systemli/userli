<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UsersRestoreCommand;
use App\Entity\User;
use App\Handler\PasswordStrengthHandler;
use App\Repository\UserRepository;
use App\Service\ConsolePasswordHelper;
use App\Service\UserRestoreService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UsersRestoreCommandTest extends TestCase
{
    private UsersRestoreCommand $command;
    private UserRestoreService&\PHPUnit\Framework\MockObject\Stub $userRestoreService;
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User('deleted@example.org');
        $this->user->setDeleted(true);

        $repository = $this->createStub(UserRepository::class);
        $repository->method('findByEmail')
            ->willReturn($this->user);

        $this->userRestoreService = $this->createStub(UserRestoreService::class);
        $consolePasswordHelper = new ConsolePasswordHelper(new PasswordStrengthHandler());

        $this->command = new UsersRestoreCommand($repository, $this->userRestoreService, $consolePasswordHelper);
    }

    public function testExecuteWithoutMailCrypt(): void
    {
        $this->userRestoreService->method('restoreUser')->willReturnCallback(static function (User $user, string $password) {
            $user->setDeleted(false);

            return null;
        });

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:restore');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['longtestpassword1234', 'longtestpassword1234']);

        // Test dry run
        $commandTester->execute(['--user' => 'deleted@example.org', '--dry-run' => true]);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Would restore user deleted@example.org', $output);

        // Test real run
        $commandTester->execute(['--user' => 'deleted@example.org']);

        // Verify that user properties got restored
        self::assertFalse($this->user->isDeleted());
        self::assertFalse($this->user->getMailCryptEnabled());

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Restoring user deleted@example.org', $output);
        self::assertStringNotContainsString('New recovery token (please hand over to user): ', $output);
    }

    public function testExecuteWithMailCrypt(): void
    {
        $this->userRestoreService->method('restoreUser')->willReturnCallback(static function (User $user, string $password) {
            $user->setDeleted(false);
            $user->setMailCryptEnabled(true);

            return 'RecoverySecret';
        });

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:restore');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['longtestpassword1234', 'longtestpassword1234']);

        // Test real run
        $commandTester->execute(['--user' => 'deleted@example.org']);

        // Verify that user properties got restored
        self::assertFalse($this->user->isDeleted());
        self::assertTrue($this->user->getMailCryptEnabled());

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Restoring user deleted@example.org', $output);
        self::assertStringContainsString('New recovery token (please hand over to user): RecoverySecret', $output);
    }

    public function testExecuteShortPassword(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:restore');

        $commandTester = new CommandTester($command);
        // Provide 5 short passwords (max attempts)
        $commandTester->setInputs(['short', 'short', 'short', 'short', 'short']);

        $exitCode = $commandTester->execute(['--user' => 'deleted@example.org']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString("The password doesn't comply with our security policy.", $commandTester->getDisplay());
    }

    public function testExecutePasswordsDontMatch(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:restore');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['longtestpassword1234', 'different']);

        $exitCode = $commandTester->execute(['--user' => 'deleted@example.org']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString("The passwords don't match.", $commandTester->getDisplay());
    }

    public function testExecuteWithoutUser(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:restore');

        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('User with email', $commandTester->getDisplay());
    }

    public function testCommandConfiguration(): void
    {
        $application = new Application();
        $application->addCommand($this->command);
        $command = $application->find('app:users:restore');

        self::assertEquals('app:users:restore', $command->getName());
        self::assertEquals('Restore a user', $command->getDescription());

        $definition = $command->getDefinition();
        self::assertTrue($definition->hasOption('user'));
        self::assertEquals('u', $definition->getOption('user')->getShortcut());
        self::assertTrue($definition->hasOption('dry-run'));
    }
}
