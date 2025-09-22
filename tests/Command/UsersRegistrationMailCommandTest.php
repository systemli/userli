<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UsersRegistrationMailCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Sender\WelcomeMessageSender;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class UsersRegistrationMailCommandTest extends TestCase
{
    private UsersRegistrationMailCommand $command;
    private MockObject $entityManager;
    private MockObject $welcomeMessageSender;
    private MockObject $userRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->welcomeMessageSender = $this->createMock(WelcomeMessageSender::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->entityManager->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        $this->command = new UsersRegistrationMailCommand(
            $this->entityManager,
            $this->welcomeMessageSender,
            'en'
        );
    }

    public function testExecuteWithValidUser(): void
    {
        $email = 'user@example.com';
        $locale = 'de';
        $user = $this->createMock(User::class);

        $this->userRepository->expects(self::once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->welcomeMessageSender->expects(self::once())
            ->method('send')
            ->with($user, $locale);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:users:registration:mail');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--user' => $email,
            '--locale' => $locale,
        ]);

        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteWithDefaultLocale(): void
    {
        $email = 'user@example.org';
        $defaultLocale = 'en';
        $user = $this->createMock(User::class);

        $this->userRepository->expects(self::once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->welcomeMessageSender->expects(self::once())
            ->method('send')
            ->with($user, $defaultLocale);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:users:registration:mail');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--user' => $email,
            // No locale option provided, should use default
        ]);

        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteWithNonExistentUser(): void
    {
        $email = 'nonexistent@example.com';

        $this->userRepository->expects(self::once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->welcomeMessageSender->expects(self::never())
            ->method('send');

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:users:registration:mail');
        $commandTester = new CommandTester($command);

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User with email nonexistent@example.com not found!');

        $commandTester->execute([
            '--user' => $email,
        ]);
    }

    public function testExecuteWithEmptyEmail(): void
    {
        $this->userRepository->expects(self::never())
            ->method('findByEmail');

        $this->welcomeMessageSender->expects(self::never())
            ->method('send');

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:users:registration:mail');
        $commandTester = new CommandTester($command);

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User with email  not found!');

        $commandTester->execute([
            '--user' => '',
        ]);
    }
}
