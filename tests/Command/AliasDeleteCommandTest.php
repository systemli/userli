<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\AliasDeleteCommand;
use App\Entity\Alias;
use App\Entity\User;
use App\Handler\DeleteHandler;
use App\Repository\AliasRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AliasDeleteCommandTest extends TestCase
{
    private AliasDeleteCommand $command;

    protected function setUp(): void
    {
        $user = new User('user@example.org');

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findByEmail')
            ->willReturn($user);

        $alias = new Alias();
        $alias->setSource('alias@example.org');
        $alias->setUser($user);

        $aliasRepository = $this->createStub(AliasRepository::class);
        $aliasRepository->method('findOneBySource')
            ->willReturn($alias);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnMap([
            [User::class, $userRepository],
            [Alias::class, $aliasRepository],
        ]);

        $deleteHandler = $this->createStub(DeleteHandler::class);

        $this->command = new AliasDeleteCommand($manager, $deleteHandler);
    }

    public function testExecute(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:alias:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--user' => 'user@example.org', '--alias' => 'alias@example.org']);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Deleting alias alias@example.org of user user@example.org', $output);

        // Test dry run alias deletion
        $commandTester->execute(['--user' => 'user@example.org', '--alias' => 'alias@example.org', '--dry-run' => true]);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Would delete alias alias@example.org of user user@example.org', $output);

        // Test alias deletion without user
        $commandTester->execute(['--alias' => 'alias@example.org']);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Deleting alias alias@example.org', $output);
    }

    public function testExecuteWithoutAlias(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:alias:delete');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Alias with address \'\' not found!', $output);
        self::assertEquals(1, $commandTester->getStatusCode());
    }
}
