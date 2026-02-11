<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Handler\UserRegistrationInfoHandler;
use App\Message\SendWeeklyReport;
use App\Service\SettingsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendWeeklyReportHandler
{
    public function __construct(
        private UserRegistrationInfoHandler $handler,
        private SettingsService $settingsService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendWeeklyReport $message): void
    {
        if (!$this->settingsService->get('weekly_report_enabled', true)) {
            $this->logger->info('Weekly report is disabled, skipping');

            return;
        }

        $this->handler->sendReport();
    }
}
