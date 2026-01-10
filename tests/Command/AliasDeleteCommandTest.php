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

        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->method('findByEmail')
            ->willReturn($user);

        $alias = new Alias();
        $alias->setSource('alias@example.org');
        $alias->setUser($user);

        $aliasRepository = $this->getMockBuilder(AliasRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $aliasRepository->method('findOneBySource')
            ->willReturn($alias);

        $manager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $manager->method('getRepository')->willReturnMap([
            [User::class, $userRepository],
            [Alias::class, $aliasRepository],
        ]);

        $deleteHandler = $this->getMockBuilder(DeleteHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

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
        $this->assertStringContainsString('Deleting alias alias@example.org of user user@example.org', $output);

        // Test dry run alias deletion
        $commandTester->execute(['--user' => 'user@example.org', '--alias' => 'alias@example.org', '--dry-run' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Would delete alias alias@example.org of user user@example.org', $output);

        // Test alias deletion without user
        $commandTester->execute(['--alias' => 'alias@example.org']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Deleting alias alias@example.org', $output);
    }

    public function testExecuteWithoutAlias(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:alias:delete');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Alias with address \'\' not found!', $output);
        $this->assertEquals($commandTester->getStatusCode(), 1);
    }
}
