<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Handler\UserRegistrationInfoHandler;
use App\Message\SendWeeklyReport;
use App\MessageHandler\SendWeeklyReportHandler;
use App\Service\SettingsService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SendWeeklyReportHandlerTest extends TestCase
{
    public function testSendsReportWhenEnabled(): void
    {
        $message = new SendWeeklyReport();

        $settingsService = $this->createMock(SettingsService::class);
        $settingsService->expects($this->once())
            ->method('get')
            ->with('weekly_report_enabled', true)
            ->willReturn(true);

        $handler = $this->createMock(UserRegistrationInfoHandler::class);
        $handler->expects($this->once())->method('sendReport');

        $logger = $this->createStub(LoggerInterface::class);

        $sendWeeklyReportHandler = new SendWeeklyReportHandler($handler, $settingsService, $logger);
        $sendWeeklyReportHandler($message);
    }

    public function testSkipsReportWhenDisabled(): void
    {
        $message = new SendWeeklyReport();

        $settingsService = $this->createMock(SettingsService::class);
        $settingsService->expects($this->once())
            ->method('get')
            ->with('weekly_report_enabled', true)
            ->willReturn(false);

        $handler = $this->createMock(UserRegistrationInfoHandler::class);
        $handler->expects($this->never())->method('sendReport');

        $logger = $this->createStub(LoggerInterface::class);

        $sendWeeklyReportHandler = new SendWeeklyReportHandler($handler, $settingsService, $logger);
        $sendWeeklyReportHandler($message);
    }
}
