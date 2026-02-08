<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\OpenPgpExportKeysCommand;
use App\Entity\Domain;
use App\Entity\OpenPgpKey;
use App\Handler\WkdHandler;
use App\Repository\DomainRepository;
use App\Repository\OpenPgpKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class OpenPgpExportKeysCommandTest extends TestCase
{
    private OpenPgpExportKeysCommand $command;

    protected function setUp(): void
    {
        $openPgpKey = new OpenPgpKey();
        $openPgpKey->setEmail('admin@example.org');

        $domain = new Domain();
        $domain->setName('example.org');

        $manager = $this->createStub(EntityManagerInterface::class);

        $domainRepository = $this->createStub(DomainRepository::class);
        $domainRepository->method('findAll')->willReturn([$domain]);

        $openPgpKeyRepository = $this->createStub(OpenPgpKeyRepository::class);
        $openPgpKeyRepository->method('findAll')->willReturn([$openPgpKey]);

        $manager->method('getRepository')->willReturnMap(
            [
                [Domain::class, $domainRepository],
                [OpenPgpKey::class, $openPgpKeyRepository],
            ]
        );

        $wkdHandler = $this->createStub(WkdHandler::class);

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
