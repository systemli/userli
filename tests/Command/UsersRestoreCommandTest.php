<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UsersRestoreCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserRestoreService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UsersRestoreCommandTest extends TestCase
{
    private UsersRestoreCommand $command;
    private UserRestoreService $userRestoreService;
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User('deleted@example.org');
        $this->user->setDeleted(true);

        $repository = $this->createStub(UserRepository::class);
        $repository->method('findByEmail')
            ->willReturn($this->user);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $this->userRestoreService = $this->createStub(UserRestoreService::class);

        $this->command = new UsersRestoreCommand($manager, $this->userRestoreService);
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
}
