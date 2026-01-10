<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UsersRemoveCommand;
use App\Entity\Domain;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use const DIRECTORY_SEPARATOR;

class UsersRemoveCommandTest extends TestCase
{
    private UsersRemoveCommand $command;
    private MockObject $entityManager;
    private MockObject $userRepository;
    private string $mailLocation = '/tmp/test-mail';

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->entityManager->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        $this->command = new UsersRemoveCommand(
            $this->entityManager,
            $this->mailLocation
        );
    }

    public function testExecuteWithDryRun(): void
    {
        $domain = $this->createMock(Domain::class);
        $domain->method('getName')->willReturn('example.com');

        $user1 = $this->createMock(User::class);
        $user1->method('getDomain')->willReturn($domain);
        $user1->method('getEmail')->willReturn('user1@example.com');
        $user1->method('__toString')->willReturn('user1@example.com');

        $user2 = $this->createMock(User::class);
        $user2->method('getDomain')->willReturn($domain);
        $user2->method('getEmail')->willReturn('user2@example.com');
        $user2->method('__toString')->willReturn('user2@example.com');

        $deletedUsers = [$user1, $user2];

        $this->userRepository->expects(self::once())
            ->method('findDeletedUsers')
            ->willReturn($deletedUsers);

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:remove');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--dry-run' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Found 2 users to delete', $output);
        self::assertStringContainsString('Would delete directory for user: user1@example.com', $output);
        self::assertStringContainsString('Would delete directory for user: user2@example.com', $output);
    }

    public function testExecuteWithList(): void
    {
        $domain = $this->createMock(Domain::class);
        $domain->method('getName')->willReturn('example.org');

        $user = $this->createMock(User::class);
        $user->method('getDomain')->willReturn($domain);
        $user->method('getEmail')->willReturn('testuser@example.org');

        $deletedUsers = [$user];

        $this->userRepository->expects(self::once())
            ->method('findDeletedUsers')
            ->willReturn($deletedUsers);

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:remove');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--list' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $expectedPath = $this->mailLocation.DIRECTORY_SEPARATOR.'example.org'.DIRECTORY_SEPARATOR.'testuser';
        self::assertStringContainsString($expectedPath, $output);
        self::assertStringNotContainsString('Found', $output); // List mode doesn't show count
    }

    public function testExecuteSkipsUsersWithoutDomain(): void
    {
        $userWithDomain = $this->createMock(User::class);
        $domain = $this->createMock(Domain::class);
        $domain->method('getName')->willReturn('example.com');
        $userWithDomain->method('getDomain')->willReturn($domain);
        $userWithDomain->method('getEmail')->willReturn('user@example.com');
        $userWithDomain->method('__toString')->willReturn('user@example.com');

        $userWithoutDomain = $this->createMock(User::class);
        $userWithoutDomain->method('getDomain')->willReturn(null);

        $deletedUsers = [$userWithDomain, $userWithoutDomain];

        $this->userRepository->expects(self::once())
            ->method('findDeletedUsers')
            ->willReturn($deletedUsers);

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:remove');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--dry-run' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Found 2 users to delete', $output);
        self::assertStringContainsString('Would delete directory for user: user@example.com', $output);
        // User without domain should be skipped silently
        self::assertEquals(1, substr_count($output, 'Would delete directory for user:'));
    }

    public function testExecuteWithNoDeletedUsers(): void
    {
        $this->userRepository->expects(self::once())
            ->method('findDeletedUsers')
            ->willReturn([]);

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:remove');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Found 0 users to delete', $output);
    }

    public function testExecuteGeneratesCorrectPath(): void
    {
        $domain = $this->createMock(Domain::class);
        $domain->method('getName')->willReturn('test.org');

        $user = $this->createMock(User::class);
        $user->method('getDomain')->willReturn($domain);
        $user->method('getEmail')->willReturn('myuser@test.org');

        $deletedUsers = [$user];

        $this->userRepository->expects(self::once())
            ->method('findDeletedUsers')
            ->willReturn($deletedUsers);

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:remove');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--list' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $expectedPath = $this->mailLocation.DIRECTORY_SEPARATOR.'test.org'.DIRECTORY_SEPARATOR.'myuser';
        self::assertStringContainsString($expectedPath, $output);
    }
}
