<?php

namespace App\Tests\Command;

use App\Command\ImportReservedNamesCommand;
use App\Repository\ReservedNameRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ImportReservedNamesCommandTest extends TestCase
{
    public function testExecuteDefaultFile()
    {
        $manager = $this->getManager();

        $validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();
        $validator->expects($this->any())->method('validate')->willReturn(new ConstraintViolationList());

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $application = new Application();
        $application->add(new ImportReservedNamesCommand($manager, $validator, $eventDispatcher));
        $commandTester = new CommandTester($command = $application->find('usrmgmt:reservednames:import'));

        $commandTester->execute(
            ['command' => $command->getName()],
            ['verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE]
        );

        $output = $commandTester->getDisplay();
        $this->assertContains('Adding reserved name "new" to database table', $output);
        $this->assertContains('Skipping reserved name "name", already exists', $output);
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
