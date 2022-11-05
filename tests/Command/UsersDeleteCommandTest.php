<?php

namespace App\Tests\Command;

use App\Command\UsersDeleteCommand;
use App\Entity\User;
use App\Handler\DeleteHandler;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UsersDeleteCommandTest extends TestCase
{
    private UsersDeleteCommand $command;

    public function setUp(): void
    {
        $user = new User();
        $user->setEmail('user@example.org');

        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->method('findByEmail')
            ->willReturn($user);

        $manager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $manager->method('getRepository')->willReturn($repository);

        $deleteHandler = $this->getMockBuilder(DeleteHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new UsersDeleteCommand($manager, $deleteHandler);
    }

    public function testExecute(): void
    {
        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:users:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--user' => 'user@example.org']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Deleting user user@example.org', $output);

        // Test dry run user deletion
        $commandTester->execute(['--user' => 'user@example.org', '--dry-run' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Would delete user user@example.org', $output);
    }

    public function testExecuteWithoutUser(): void
    {
        $this->expectException(UsernameNotFoundException::class);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:users:delete');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
    }
}
