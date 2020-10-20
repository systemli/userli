<?php

namespace App\Tests\Command;

use App\Command\OpenPgpExportKeysCommand;
use App\Entity\OpenPgpKey;
use App\Handler\WkdHandler;
use App\Repository\OpenPgpKeyRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class OpenPgpExportKeysCommandTest extends TestCase
{
    /**
     * @var OpenPgpExportKeysCommand
     */
    private $command;

    public function setUp(): void
    {
        $openPgpKey = new OpenPgpKey();
        $openPgpKey->setEmail('admin@example.org');

        $manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder(OpenPgpKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->method('findAll')->willReturn([$openPgpKey]);

        $manager->method('getRepository')->willReturn($repository);

        $wkdHandler = $this->getMockBuilder(WkdHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new OpenPgpExportKeysCommand($manager, $wkdHandler);
    }

    public function testExecute(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Exported 1 OpenPGP keys to WKD directory', $output);
    }
}
