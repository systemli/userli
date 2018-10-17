<?php

namespace App\Tests\Command;

use App\Command\MuninAccountCommand;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MuninAccountCommandTest extends TestCase
{
    public function testExecute()
    {
        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())->method('count')->willReturn(10);

        $manager = $this->getMockBuilder(ObjectManager::class)
            ->getMock();
        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        $command = new MuninAccountCommand($manager);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        self::assertEquals("account.value 10\n", $output);

        $commandTester->execute(['--autoconf' => true]);

        $output = $commandTester->getDisplay();

        self::assertEquals("yes\n", $output);

        $commandTester->execute(['--config' => true]);

        $output = $commandTester->getDisplay();

        self::assertContains('graph_title User Accounts', $output);
        self::assertContains('graph_category Mail', $output);
        self::assertContains('graph_vlabel Account Counters', $output);
        self::assertContains('account.label Total Accounts', $output);
        self::assertContains('account.type GAUGE', $output);
        self::assertContains('account.min 0', $output);
    }
}
