<?php

namespace App\Tests\Command;

use App\Command\ReservedNamesImportCommand;
use App\Creator\ReservedNameCreator;
use App\Entity\ReservedName;
use App\Repository\ReservedNameRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ReservedNamesImportCommandTest extends TestCase
{
    public function testExecuteDefaultFile(): void
    {
        $manager = $this->getManager();

        $creator = $this->getMockBuilder(ReservedNameCreator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $creator->method('create')->willReturn(new ReservedName());

        $command = new ReservedNamesImportCommand($manager, $creator);
        $commandTester = new CommandTester($command);

        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Adding reserved name "new" to database table', $output);
        $this->assertStringContainsString('Skipping reserved name "name", already exists', $output);
    }

    public function getManager(): EntityManagerInterface
    {
        $manager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder(ReservedNameRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->method('findByName')->willReturnMap(
            [
                ['new', null],
                ['name', new ReservedName()],
            ]
        );

        $manager->method('getRepository')->willReturn($repository);

        return $manager;
    }
}
