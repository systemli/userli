<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\AdminPasswordCommand;
use App\Handler\PasswordStrengthHandler;
use App\Helper\AdminPasswordUpdater;
use App\Service\ConsolePasswordHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AdminPasswordCommandTest extends TestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $updater = $this->createStub(AdminPasswordUpdater::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());
        $consolePasswordHelper = new ConsolePasswordHelper(new PasswordStrengthHandler(), $validator);

        $command = new AdminPasswordCommand($updater, $consolePasswordHelper);
        $app = new Application();
        $app->addCommand($command);

        $this->commandTester = new CommandTester($app->find('app:admin:password'));
    }

    public function testExecute(): void
    {
        $exitCode = $this->commandTester->execute(['password' => 'longtestpassword1234']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertEquals('', $this->commandTester->getDisplay());
    }

    public function testExecuteInteractive(): void
    {
        $this->commandTester->setInputs(['longtestpassword1234', 'longtestpassword1234']);

        $exitCode = $this->commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('New password:', $this->commandTester->getDisplay());
    }

    public function testExecuteShortPasswordInteractive(): void
    {
        $this->commandTester->setInputs(['short', 'short', 'short', 'short', 'short']);

        $exitCode = $this->commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString("The password doesn't comply with our security policy.", $this->commandTester->getDisplay());
    }

    public function testExecutePasswordsDontMatch(): void
    {
        $this->commandTester->setInputs(['longtestpassword1234', 'different']);

        $exitCode = $this->commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString("The passwords don't match.", $this->commandTester->getDisplay());
    }

    public function testCommandConfiguration(): void
    {
        $updater = $this->createStub(AdminPasswordUpdater::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());
        $consolePasswordHelper = new ConsolePasswordHelper(new PasswordStrengthHandler(), $validator);

        $command = new AdminPasswordCommand($updater, $consolePasswordHelper);

        $app = new Application();
        $app->addCommand($command);
        $wrappedCommand = $app->find('app:admin:password');

        self::assertEquals('app:admin:password', $wrappedCommand->getName());
        self::assertEquals('Set password of admin user', $wrappedCommand->getDescription());

        $definition = $wrappedCommand->getDefinition();
        self::assertTrue($definition->hasArgument('password'));
        self::assertFalse($definition->getArgument('password')->isRequired());
    }
}
