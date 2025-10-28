<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UsersQuotaCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class UsersQuotaCommandTest extends TestCase
{
    private UsersQuotaCommand $command;
    private MockObject $entityManager;
    private MockObject $userRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->entityManager->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        $this->command = new UsersQuotaCommand($this->entityManager);
    }

    public function testExecuteWithQuotaSet(): void
    {
        $email = 'quota@example.org';
        $quota = 1000;

        $user = $this->createMock(User::class);
        $user->method('getQuota')->willReturn($quota);

        $this->userRepository->expects(self::once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:users:quota');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--user' => $email,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('1000', $output);
    }

    public function testExecuteWithoutQuota(): void
    {
        $email = 'noquota@example.org';

        $user = $this->createMock(User::class);
        $user->method('getQuota')->willReturn(null);

        $this->userRepository->expects(self::once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:users:quota');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--user' => $email,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertStringNotContainsString('1000', $output);
        self::assertEmpty(trim($output));
    }

    public function testExecuteWithUnknownUser(): void
    {
        $email = 'unknown@example.org';

        $this->userRepository->expects(self::once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:users:quota');
        $commandTester = new CommandTester($command);

        $this->expectException(UserNotFoundException::class);

        $commandTester->execute([
            '--user' => $email,
        ]);
    }
}
