<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UserNotificationCleanupCommand;
use App\Repository\UserNotificationRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UserNotificationCleanupCommandTest extends TestCase
{
    private UserNotificationRepository|MockObject $repository;
    private UserNotificationCleanupCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserNotificationRepository::class);
        $this->command = new UserNotificationCleanupCommand($this->repository);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:user:notification:cleanup');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithDefaultOptions(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('cleanupOldNotifications')
            ->with(30, null)
            ->willReturn(15);

        $this->commandTester->execute([]);

        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Cleaning up all notifications older than 30 days...', $output);
        $this->assertStringContainsString('Successfully deleted 15 old notification records', $output);
    }

    public function testExecuteWithCustomDays(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('cleanupOldNotifications')
            ->with(7, null)
            ->willReturn(3);

        $this->commandTester->execute([
            '--days' => '7',
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Cleaning up all notifications older than 7 days...', $output);
        $this->assertStringContainsString('Successfully deleted 3 old notification records', $output);
    }

    public function testExecuteWithSpecificType(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('cleanupOldNotifications')
            ->with(30, 'password_compromised')
            ->willReturn(8);

        $this->commandTester->execute([
            '--type' => 'password_compromised',
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Cleaning up password_compromised notifications older than 30 days...', $output);
        $this->assertStringContainsString('Successfully deleted 8 old password_compromised notification records', $output);
    }

    public function testExecuteWithCustomDaysAndType(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('cleanupOldNotifications')
            ->with(14, 'password_compromised')
            ->willReturn(2);

        $this->commandTester->execute([
            '--days' => '14',
            '--type' => 'password_compromised',
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Cleaning up password_compromised notifications older than 14 days...', $output);
        $this->assertStringContainsString('Successfully deleted 2 old password_compromised notification records', $output);
    }

    public function testExecuteWithShortOptions(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('cleanupOldNotifications')
            ->with(5, 'password_compromised')
            ->willReturn(1);

        $this->commandTester->execute([
            '-d' => '5',
            '-t' => 'password_compromised',
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Cleaning up password_compromised notifications older than 5 days...', $output);
        $this->assertStringContainsString('Successfully deleted 1 old password_compromised notification records', $output);
    }

    public function testExecuteWithInvalidDays(): void
    {
        // Repository should not be called with invalid input
        $this->repository->expects($this->never())->method('cleanupOldNotifications');

        $this->commandTester->execute([
            '--days' => '0',
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Days must be a positive number', $output);
    }

    public function testExecuteWithNegativeDays(): void
    {
        // Repository should not be called with invalid input
        $this->repository->expects($this->never())->method('cleanupOldNotifications');

        $this->commandTester->execute([
            '--days' => '-5',
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Days must be a positive number', $output);
    }

    public function testExecuteWithRepositoryException(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('cleanupOldNotifications')
            ->with(30, null)
            ->willThrowException(new \Exception('Database connection failed'));

        $this->commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Cleaning up all notifications older than 30 days...', $output);
        $this->assertStringContainsString('Error during cleanup: Database connection failed', $output);
    }

    public function testExecuteWithZeroDeletedRecords(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('cleanupOldNotifications')
            ->with(30, null)
            ->willReturn(0);

        $this->commandTester->execute([]);

        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Cleaning up all notifications older than 30 days...', $output);
        $this->assertStringContainsString('Successfully deleted 0 old notification records', $output);
    }

    public function testExecuteWithLargeNumberOfDeletedRecords(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('cleanupOldNotifications')
            ->with(90, 'password_compromised')
            ->willReturn(1234);

        $this->commandTester->execute([
            '--days' => '90',
            '--type' => 'password_compromised',
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Cleaning up password_compromised notifications older than 90 days...', $output);
        $this->assertStringContainsString('Successfully deleted 1234 old password_compromised notification records', $output);
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->command;

        $this->assertEquals('app:user:notification:cleanup', $command->getName());
        $this->assertEquals('Clean up old user notifications', $command->getDescription());

        $definition = $command->getDefinition();
        
        // Test days option
        $this->assertTrue($definition->hasOption('days'));
        $daysOption = $definition->getOption('days');
        $this->assertEquals('d', $daysOption->getShortcut());
        $this->assertEquals(30, $daysOption->getDefault());
        $this->assertStringContainsString('Number of days to keep notifications', $daysOption->getDescription());

        // Test type option
        $this->assertTrue($definition->hasOption('type'));
        $typeOption = $definition->getOption('type');
        $this->assertEquals('t', $typeOption->getShortcut());
        $this->assertNull($typeOption->getDefault());
        $this->assertStringContainsString('Notification type to clean up', $typeOption->getDescription());
    }
}
