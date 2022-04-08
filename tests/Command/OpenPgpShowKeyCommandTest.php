<?php

namespace App\Tests\Command;

use App\Command\OpenPgpShowKeyCommand;
use App\Entity\OpenPgpKey;
use App\Handler\WkdHandler;
use App\Repository\OpenPgpKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class OpenPgpShowKeyCommandTest extends TestCase
{
    /**
     * @var OpenPgpShowKeyCommand
     */
    private $command;

    public function setUp(): void
    {
        $openPgpKey = new OpenPgpKey();
        $openPgpKey->setEmail('alice@example.org');

        $manager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder(OpenPgpKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->method('findByEmail')->willReturnMap(
            [
                ['alice@example.org', $openPgpKey],
                ['nonexistent@example.org', null],
            ]
        );

        $manager->method('getRepository')->willReturn($repository);

        $wkdHandler = $this->getMockBuilder(WkdHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new OpenPgpShowKeyCommand($manager, $wkdHandler);
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
}
