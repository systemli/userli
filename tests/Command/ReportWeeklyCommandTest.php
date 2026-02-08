<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ReportWeeklyCommand;
use App\Handler\UserRegistrationInfoHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ReportWeeklyCommandTest extends TestCase
{
    public function testExecuteCallsSendReport(): void
    {
        $handler = $this->createMock(UserRegistrationInfoHandler::class);
        $handler->expects($this->once())->method('sendReport');

        $command = new ReportWeeklyCommand($handler);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
    }
}
