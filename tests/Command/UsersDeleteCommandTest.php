<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UsersDeleteCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserLifecycleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UsersDeleteCommandTest extends TestCase
{
    private UsersDeleteCommand $command;

    protected function setUp(): void
    {
        $user = new User('user@example.org');

        $repository = $this->createStub(UserRepository::class);
        $repository->method('findByEmail')
            ->willReturn($user);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $userLifecycleService = $this->createStub(UserLifecycleService::class);

        $this->command = new UsersDeleteCommand($manager, $userLifecycleService);
    }

    public function testExecute(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--user' => 'user@example.org']);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Deleting user user@example.org', $output);

        // Test dry run user deletion
        $commandTester->execute(['--user' => 'user@example.org', '--dry-run' => true]);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Would delete user user@example.org', $output);
    }

    public function testExecuteWithoutUser(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:delete');

        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('User with email', $commandTester->getDisplay());
    }
}
