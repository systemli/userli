<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UsersRestoreCommand;
use App\Entity\User;
use App\Handler\UserRestoreHandler;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UsersRestoreCommandTest extends TestCase
{
    private UsersRestoreCommand $command;
    private UserRestoreHandler $userRestoreHandler;
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User('deleted@example.org');
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

        $this->userRestoreHandler = $this->getMockBuilder(UserRestoreHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new UsersRestoreCommand($manager, $this->userRestoreHandler);
    }

    public function testExecuteWithoutMailCrypt(): void
    {
        $this->userRestoreHandler->method('restoreUser')->willReturnCallback(function (User $user, string $password) {
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
        $this->assertStringContainsString('Would restore user deleted@example.org', $output);

        // Test real run
        $commandTester->execute(['--user' => 'deleted@example.org']);

        // Verify that user properties got restored
        self::assertFalse($this->user->isDeleted());
        self::assertFalse($this->user->getMailCryptEnabled());

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Restoring user deleted@example.org', $output);
        $this->assertStringNotContainsString('New recovery token (please hand over to user): ', $output);
    }

    public function testExecuteWithMailCrypt(): void
    {
        $this->userRestoreHandler->method('restoreUser')->willReturnCallback(function (User $user, string $password) {
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
        $this->assertStringContainsString('Restoring user deleted@example.org', $output);
        $this->assertStringContainsString('New recovery token (please hand over to user): RecoverySecret', $output);
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
        $this->assertStringContainsString("The password doesn't comply with our security policy.", $commandTester->getDisplay());
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
        $this->assertStringContainsString("The passwords don't match.", $commandTester->getDisplay());
    }

    public function testExecuteWithoutUser(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:restore');

        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('User with email', $commandTester->getDisplay());
    }
}
