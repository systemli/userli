<?php

namespace App\Tests\Command;

use App\Command\ImportReservedNamesCommand;
use App\Repository\ReservedNameRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ImportReservedNamesCommandTest extends TestCase
{
    public function testExecuteDefaultFile()
    {
        $manager = $this->getManager();
        $application = new Application();
        $application->add(new ImportReservedNamesCommand($manager));
        $commandTester = new CommandTester($command = $application->find('usrmgmt:reservednames:import'));

        $commandTester->execute(
            ['command' => $command->getName()],
            ['verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE]
        );

        $output = $commandTester->getDisplay();
        $this->assertContains('Adding reservedName "new" to database', $output);
        $this->assertContains('Skipping reservedName "name", already exists', $output);
    }

    public function getManager()
    {
        $manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder(ReservedNameRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->method('findByName')->willReturnMap([
                ['new', null],
                ['name', true],
            ]);

        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        return $manager;
    }
}
