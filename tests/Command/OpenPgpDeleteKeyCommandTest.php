<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\OpenPgpDeleteKeyCommand;
use App\Entity\OpenPgpKey;
use App\Service\OpenPgpKeyManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class OpenPgpDeleteKeyCommandTest extends TestCase
{
    private OpenPgpDeleteKeyCommand $command;

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

        $this->command = new OpenPgpDeleteKeyCommand($manager);
    }

    public function testExecute(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['email' => 'alice@example.org']);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Deleted OpenPGP key for email alice@example.org', $output);
    }

    public function testExecuteWithNonexistentEmail(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['email' => 'nonexistent@example.org']);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('No OpenPGP key found for email nonexistent@example.org', $output);
    }
}
