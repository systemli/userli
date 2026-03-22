<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UsersResetCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserResetService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

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

        $repository = $this->createStub(UserRepository::class);
        $repository->method('findByEmail')
            ->willReturn($this->user);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $userResetService = $this->createStub(UserResetService::class);

        $this->command = new UsersResetCommand($manager, $userResetService);
    }

    public function testExecuteDryRun(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:reset');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes', 'longtestpassword1234', 'longtestpassword1234']);

        $commandTester->execute(['--user' => 'user@example.org', '--dry-run' => true]);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Would reset user user@example.org', $output);
    }

    public function testExecute(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:reset');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes', 'longtestpassword1234', 'longtestpassword1234']);

        $commandTester->execute(['--user' => 'user@example.org']);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Resetting user user@example.org', $output);
    }

    public function testExecuteShortPassword(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:reset');

        $commandTester = new CommandTester($command);
        // Provide 5 short passwords (max attempts) + confirmation question
        $commandTester->setInputs(['yes', 'short', 'short', 'short', 'short', 'short']);

        $exitCode = $commandTester->execute(['--user' => 'user@example.org']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString("The password doesn't comply with our security policy.", $commandTester->getDisplay());
    }

    public function testExecutePasswordsDontMatch(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:reset');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes', 'longtestpassword1234', 'different']);

        $exitCode = $commandTester->execute(['--user' => 'user@example.org']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString("The passwords don't match.", $commandTester->getDisplay());
    }

    public function testExecuteWithoutUser(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:reset');

        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('User with email', $commandTester->getDisplay());
    }
}
