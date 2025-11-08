<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Handler\MailHandler;
use App\Handler\SuspiciousChildrenHandler;
use App\Service\SettingsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class SuspiciousChildrenHandlerTest extends TestCase
{
    private SuspiciousChildrenHandler $handler;
    private MockObject $mailHandler;
    private MockObject $twig;
    private MockObject $settingsService;

    protected function setUp(): void
    {
        $this->mailHandler = $this->createMock(MailHandler::class);
        $this->twig = $this->createMock(Environment::class);
        $this->settingsService = $this->createMock(SettingsService::class);

        $this->handler = new SuspiciousChildrenHandler(
            $this->mailHandler,
            $this->twig,
            $this->settingsService
        );
    }

    public function testSendReport(): void
    {
        $suspiciousChildren = [
            ['email' => 'user1@example.com', 'invitations' => 5],
            ['email' => 'user2@example.com', 'invitations' => 3],
        ];
        $renderedMessage = 'Suspicious users report: user1@example.com (5), user2@example.com (3)';
        $notificationEmail = 'admin@example.com';
        $expectedSubject = 'Suspicious users invited more users';

        $this->twig->expects(self::once())
            ->method('render')
            ->with('Email/suspicious_children.twig', ['suspiciousChildren' => $suspiciousChildren])
            ->willReturn($renderedMessage);

        $this->settingsService->expects(self::once())
            ->method('get')
            ->with('email_notification_address')
            ->willReturn($notificationEmail);

        $this->mailHandler->expects(self::once())
            ->method('send')
            ->with($notificationEmail, $renderedMessage, $expectedSubject);

        $this->handler->sendReport($suspiciousChildren);
    }

    public function testSendReportWithEmptyArray(): void
    {
        $suspiciousChildren = [];
        $renderedMessage = 'No suspicious users found.';
        $notificationEmail = 'notifications@example.org';
        $expectedSubject = 'Suspicious users invited more users';

        $this->twig->expects(self::once())
            ->method('render')
            ->with('Email/suspicious_children.twig', ['suspiciousChildren' => $suspiciousChildren])
            ->willReturn($renderedMessage);

        $this->settingsService->expects(self::once())
            ->method('get')
            ->with('email_notification_address')
            ->willReturn($notificationEmail);

        $this->mailHandler->expects(self::once())
            ->method('send')
            ->with($notificationEmail, $renderedMessage, $expectedSubject);

        $this->handler->sendReport($suspiciousChildren);
    }
}
