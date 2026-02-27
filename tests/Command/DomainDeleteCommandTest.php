<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\DomainDeleteCommand;
use App\Entity\Domain;
use App\Repository\DomainRepository;
use App\Service\DomainManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DomainDeleteCommandTest extends TestCase
{
    public function testDeleteSuccess(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $repository = $this->createStub(DomainRepository::class);
        $repository->method('findByName')
            ->with('example.org')
            ->willReturn($domain);

        $manager = $this->createMock(DomainManager::class);
        $manager->method('getDomainStats')
            ->with($domain)
            ->willReturn(['users' => 5, 'aliases' => 3, 'admins' => 1, 'vouchers' => 2]);
        $manager
            ->expects($this->once())
            ->method('delete')
            ->with($domain);

        $command = new DomainDeleteCommand($repository, $manager);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute(['--domain' => 'example.org']);

        self::assertEquals(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('deleted', $commandTester->getDisplay());
    }

    public function testDeleteDryRun(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $repository = $this->createStub(DomainRepository::class);
        $repository->method('findByName')
            ->with('example.org')
            ->willReturn($domain);

        $manager = $this->createMock(DomainManager::class);
        $manager->method('getDomainStats')
            ->with($domain)
            ->willReturn(['users' => 5, 'aliases' => 3, 'admins' => 1, 'vouchers' => 2]);
        $manager
            ->expects($this->never())
            ->method('delete');

        $command = new DomainDeleteCommand($repository, $manager);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute(['--domain' => 'example.org', '--dry-run' => true]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Would delete', $output);
        self::assertStringContainsString('5 users', $output);
        self::assertStringContainsString('3 aliases', $output);
        self::assertStringContainsString('2 vouchers', $output);
    }

    public function testDeleteDomainNotFound(): void
    {
        $repository = $this->createStub(DomainRepository::class);
        $repository->method('findByName')
            ->with('nonexistent.org')
            ->willReturn(null);

        $manager = $this->createStub(DomainManager::class);

        $command = new DomainDeleteCommand($repository, $manager);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute(['--domain' => 'nonexistent.org']);

        self::assertEquals(Command::FAILURE, $exitCode);
        self::assertStringContainsString('not found', $commandTester->getDisplay());
    }

    public function testDeleteWithoutDomainOption(): void
    {
        $repository = $this->createStub(DomainRepository::class);
        $manager = $this->createStub(DomainManager::class);

        $command = new DomainDeleteCommand($repository, $manager);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([]);

        self::assertEquals(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Please provide a domain name', $commandTester->getDisplay());
    }

    public function testCommandConfiguration(): void
    {
        $repository = $this->createStub(DomainRepository::class);
        $manager = $this->createStub(DomainManager::class);

        $command = new DomainDeleteCommand($repository, $manager);

        self::assertEquals('app:domain:delete', $command->getName());

        $definition = $command->getDefinition();
        self::assertTrue($definition->hasOption('domain'));
        self::assertTrue($definition->hasOption('dry-run'));
    }
}
