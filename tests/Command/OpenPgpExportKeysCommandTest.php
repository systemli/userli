<?php

namespace App\Tests\Command;

use App\Command\OpenPgpExportKeysCommand;
use App\Entity\Domain;
use App\Entity\OpenPgpKey;
use App\Handler\WkdHandler;
use App\Repository\DomainRepository;
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

        $domain = new Domain();
        $domain->setName('example.org');

        $manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $domainRepository = $this->getMockBuilder(DomainRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $domainRepository->method('findAll')->willReturn([$domain]);

        $openPgpKeyRepository = $this->getMockBuilder(OpenPgpKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $openPgpKeyRepository->method('findAll')->willReturn([$openPgpKey]);

        $manager->method('getRepository')->willReturnMap(
            [
                ['App:Domain', $domainRepository],
                ['App:OpenPgpKey', $openPgpKeyRepository],
            ]
        );

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
