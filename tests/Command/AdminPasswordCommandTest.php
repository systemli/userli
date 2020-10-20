<?php

namespace App\Tests\Command;

use App\Command\AdminPasswordCommand;
use App\Helper\AdminPasswordUpdater;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AdminPasswordCommandTest extends TestCase
{
    public function testExecute()
    {
        $manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updater = $this->getMockBuilder(AdminPasswordUpdater::class)
            ->disableOriginalConstructor()
            ->getMock();

        $command = new AdminPasswordCommand($manager, $updater);
        $app = new Application();
        $app->add($command);
        $commandTester = new CommandTester($command);
        $helper = $command->getHelper('question');

        $commandTester->execute(['password' => 'test']);

        $output = $commandTester->getDisplay();
        $this->assertEquals('', $output);

        $commandTester->setInputs(['password via interactive command\n']);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Please enter new admin password', $output);
    }
}
