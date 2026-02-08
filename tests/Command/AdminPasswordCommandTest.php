<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\AdminPasswordCommand;
use App\Helper\AdminPasswordUpdater;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AdminPasswordCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $updater = $this->createStub(AdminPasswordUpdater::class);

        $command = new AdminPasswordCommand($updater);
        $app = new Application();
        $app->addCommand($command);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['password' => 'test']);

        $output = $commandTester->getDisplay();
        self::assertEquals('', $output);

        $commandTester->setInputs(['password via interactive command\n']);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Please enter new admin password', $output);
    }
}
