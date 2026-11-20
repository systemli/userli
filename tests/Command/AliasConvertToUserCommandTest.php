<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\AliasConvertToUserCommand;
use App\Entity\Alias;
use App\Entity\User;
use App\Exception\AliasConversionException;
use App\Repository\AliasRepository;
use App\Service\UserManager;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class AliasConvertToUserCommandTest extends TestCase
{
    private Alias $alias;
    private AliasRepository&Stub $aliasRepository;

    protected function setUp(): void
    {
        $this->alias = new Alias();
        $this->alias->setSource('alias@example.org');
        $this->alias->setDestination('user@example.org');

        $this->aliasRepository = $this->createStub(AliasRepository::class);
        $this->aliasRepository->method('findOneBySource')->willReturnCallback(
            fn (string $source): ?Alias => 'alias@example.org' === $source ? $this->alias : null
        );
    }

    public function testExecute(): void
    {
        $userManager = $this->createMock(UserManager::class);
        $userManager->expects($this->once())->method('assertConvertible')->with($this->alias);
        $userManager->expects($this->once())
            ->method('createFromAlias')
            ->with($this->alias, 'longtestpassword1234')
            ->willReturn(new User('alias@example.org'));

        $commandTester = $this->createCommandTester($userManager);
        $commandTester->setInputs(['longtestpassword1234', 'longtestpassword1234']);

        $exitCode = $commandTester->execute(['--alias' => 'alias@example.org']);

        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Converted alias alias@example.org into a new user.', $output);
    }

    public function testExecuteDryRun(): void
    {
        $userManager = $this->createMock(UserManager::class);
        $userManager->expects($this->once())->method('assertConvertible');
        $userManager->expects($this->never())->method('createFromAlias');

        $commandTester = $this->createCommandTester($userManager);

        $exitCode = $commandTester->execute(['--alias' => 'alias@example.org', '--dry-run' => true]);

        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Would delete alias alias@example.org and create a new user alias@example.org', $output);
        self::assertStringNotContainsString('New password', $output);
    }

    public function testExecuteAliasNotFound(): void
    {
        $userManager = $this->createMock(UserManager::class);
        $userManager->expects($this->never())->method('createFromAlias');

        $commandTester = $this->createCommandTester($userManager);

        $exitCode = $commandTester->execute(['--alias' => 'unknown@example.org']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString("Alias with address 'unknown@example.org' not found!", $commandTester->getDisplay());
    }

    public function testExecuteWithoutAlias(): void
    {
        $commandTester = $this->createCommandTester($this->createStub(UserManager::class));

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString("Alias with address '' not found!", $commandTester->getDisplay());
    }

    public function testExecuteRejectedAlias(): void
    {
        $this->alias->setRandom(true);

        $userManager = $this->createMock(UserManager::class);
        $userManager->method('assertConvertible')
            ->willThrowException(AliasConversionException::aliasRandom('alias@example.org'));
        $userManager->expects($this->never())->method('createFromAlias');

        $commandTester = $this->createCommandTester($userManager);

        $exitCode = $commandTester->execute(['--alias' => 'alias@example.org']);

        self::assertSame(Command::FAILURE, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Alias alias@example.org is a random alias.', $output);
        self::assertStringNotContainsString('New password', $output);
    }

    public function testExecutePasswordsDontMatch(): void
    {
        $userManager = $this->createMock(UserManager::class);
        $userManager->expects($this->never())->method('createFromAlias');

        $commandTester = $this->createCommandTester($userManager);
        $commandTester->setInputs(['longtestpassword1234', 'different']);

        $exitCode = $commandTester->execute(['--alias' => 'alias@example.org']);

        self::assertSame(Command::FAILURE, $exitCode);
    }

    private function createCommandTester(UserManager $userManager): CommandTester
    {
        $application = new Application();
        $application->addCommand(new AliasConvertToUserCommand(
            $this->aliasRepository,
            $userManager,
        ));

        return new CommandTester($application->find('app:alias:convert-to-user'));
    }
}
