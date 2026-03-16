<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\OpenPgpShowKeyCommand;
use App\Entity\OpenPgpKey;
use App\Service\OpenPgpKeyManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class OpenPgpShowKeyCommandTest extends TestCase
{
    private OpenPgpShowKeyCommand $command;

    protected function setUp(): void
    {
        $openPgpKey = new OpenPgpKey();
        $openPgpKey->setEmail('alice@example.org');

        $manager = $this->createStub(OpenPgpKeyManager::class);
        $manager->method('getKey')->willReturnMap(
            [
                ['alice@example.org', $openPgpKey],
                ['nonexistent@example.org', null],
            ]
        );

        $this->command = new OpenPgpShowKeyCommand($manager);
    }

    public function testExecute(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['email' => 'alice@example.org']);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('OpenPGP key for email alice@example.org:', $output);
    }

    public function testExecuteWithNonexistentEmail(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['email' => 'nonexistent@example.org']);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('No OpenPGP key found for email nonexistent@example.org', $output);
    }

    public function testCommandConfiguration(): void
    {
        $application = new Application();
        $application->addCommand($this->command);
        $command = $application->find('app:openpgp:show-key');

        self::assertEquals('app:openpgp:show-key', $command->getName());
        self::assertEquals('Show OpenPGP key of email', $command->getDescription());

        $definition = $command->getDefinition();
        self::assertTrue($definition->hasArgument('email'));
        self::assertTrue($definition->getArgument('email')->isRequired());
    }
}
