<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\SettingsService;
use Twig\Environment;

final readonly class SuspiciousChildrenHandler
{
    public function __construct(
        private MailHandler $handler,
        private Environment $twig,
        private SettingsService $settingsService,
    ) {
    }

    public function sendReport(array $suspiciousChildren): void
    {
        $message = $this->twig->render('Email/suspicious_children.twig', ['suspiciousChildren' => $suspiciousChildren]);
        $email = $this->settingsService->get('email_notification_address');

        $this->handler->send($email, $message, 'Suspicious users invited more users');
    }
}
