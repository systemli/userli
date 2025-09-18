<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Handler\MailHandler;
use App\Service\SettingsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailHandlerTest extends TestCase
{
    private MockObject $mailer;
    private MockObject $settingsService;
    private MailHandler $mailHandler;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->settingsService = $this->createMock(SettingsService::class);

        $this->mailHandler = new MailHandler($this->mailer, $this->settingsService);
    }

    public function testSendWithBasicParameters(): void
    {
        $email = 'user@example.com';
        $plain = 'Test email content';
        $subject = 'Test Subject';
        $senderAddress = 'noreply@example.com';
        $appName = 'Test App';

        $this->settingsService->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['sender_address', null, $senderAddress],
                ['app_name', null, $appName],
            ]);

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $message) use ($email, $plain, $subject, $senderAddress, $appName) {
                $fromAddresses = $message->getFrom();
                $toAddresses = $message->getTo();

                return count($fromAddresses) === 1
                    && $fromAddresses[0]->getAddress() === $senderAddress
                    && $fromAddresses[0]->getName() === $appName
                    && count($toAddresses) === 1
                    && $toAddresses[0]->getAddress() === $email
                    && $message->getSubject() === $subject
                    && $message->getTextBody() === $plain
                    && $message->getHtmlBody() === null;
            }));

        $this->mailHandler->send($email, $plain, $subject);
    }

    public function testSendWithBccParameter(): void
    {
        $email = 'user@example.com';
        $plain = 'Test email content';
        $subject = 'Test Subject';
        $bcc = 'admin@example.com';
        $senderAddress = 'noreply@example.com';
        $appName = 'Test App';

        $this->settingsService->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['sender_address', null, $senderAddress],
                ['app_name', null, $appName],
            ]);

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $message) use ($bcc) {
                $bccAddresses = $message->getBcc();
                return count($bccAddresses) === 1 && $bccAddresses[0]->getAddress() === $bcc;
            }));

        $this->mailHandler->send($email, $plain, $subject, ['bcc' => $bcc]);
    }

    public function testSendWithHtmlParameter(): void
    {
        $email = 'user@example.com';
        $plain = 'Test email content';
        $subject = 'Test Subject';
        $html = '<p>Test <strong>HTML</strong> content</p>';
        $senderAddress = 'noreply@example.com';
        $appName = 'Test App';

        $this->settingsService->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['sender_address', null, $senderAddress],
                ['app_name', null, $appName],
            ]);

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $message) use ($html) {
                return $message->getHtmlBody() === $html;
            }));

        $this->mailHandler->send($email, $plain, $subject, ['html' => $html]);
    }

    public function testSendWithBothBccAndHtmlParameters(): void
    {
        $email = 'user@example.com';
        $plain = 'Test email content';
        $subject = 'Test Subject';
        $bcc = 'admin@example.com';
        $html = '<p>Test <strong>HTML</strong> content</p>';
        $senderAddress = 'noreply@example.com';
        $appName = 'Test App';

        $this->settingsService->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['sender_address', null, $senderAddress],
                ['app_name', null, $appName],
            ]);

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $message) use ($bcc, $html) {
                $bccAddresses = $message->getBcc();
                return count($bccAddresses) === 1
                    && $bccAddresses[0]->getAddress() === $bcc
                    && $message->getHtmlBody() === $html;
            }));

        $this->mailHandler->send($email, $plain, $subject, ['bcc' => $bcc, 'html' => $html]);
    }

    public function testSendWithEmptyParams(): void
    {
        $email = 'user@example.com';
        $plain = 'Test content';
        $subject = 'Test Subject';
        $senderAddress = 'noreply@example.com';
        $appName = 'Test App';

        $this->settingsService->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['sender_address', null, $senderAddress],
                ['app_name', null, $appName],
            ]);

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $message) {
                // Verify BCC is empty and HTML is null when empty params array is passed
                return empty($message->getBcc()) && $message->getHtmlBody() === null;
            }));

        $this->mailHandler->send($email, $plain, $subject, []);
    }
}
