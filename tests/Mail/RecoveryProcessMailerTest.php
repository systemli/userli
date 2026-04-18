<?php

declare(strict_types=1);

namespace App\Tests\Mail;

use App\Entity\User;
use App\Handler\MailHandler;
use App\Mail\RecoveryProcessMailer;
use App\Service\SettingsService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class RecoveryProcessMailerTest extends TestCase
{
    public function testSendCallsTranslatorAndMailHandler(): void
    {
        $user = new User('user@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable('2026-01-15 10:00:00'));

        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')
            ->willReturnMap([
                ['app_url', null, 'https://mail.example.com'],
                ['project_name', null, 'Example Mail'],
            ]);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static fn (string $id) => match ($id) {
                'mail.recovery-body' => 'Recovery body',
                'mail.recovery-subject' => 'Recovery subject',
            });

        $handler = $this->createMock(MailHandler::class);
        $handler->expects(self::once())
            ->method('send')
            ->with('user@example.org', 'Recovery body', 'Recovery subject');

        $mailer = new RecoveryProcessMailer($handler, $translator, $settingsService);
        $mailer->send($user, 'en');
    }

    public function testSendPassesLocaleToTranslator(): void
    {
        $user = new User('user@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable('2026-01-15 10:00:00'));

        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturn('value');

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::exactly(2))
            ->method('trans')
            ->willReturnCallback(static function (string $id, array $parameters, ?string $domain, ?string $locale): string {
                self::assertSame('de', $locale);

                return 'translated';
            });

        $handler = $this->createMock(MailHandler::class);
        $handler->expects(self::once())->method('send');

        $mailer = new RecoveryProcessMailer($handler, $translator, $settingsService);
        $mailer->send($user, 'de');
    }

    public function testSendPassesCorrectParametersToTranslator(): void
    {
        $user = new User('user@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable('2026-01-15 10:00:00'));

        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')
            ->willReturnMap([
                ['app_url', null, 'https://mail.example.com'],
                ['project_name', null, 'Example Mail'],
            ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::exactly(2))
            ->method('trans')
            ->willReturnCallback(static function (string $id, array $parameters) {
                if ('mail.recovery-body' === $id) {
                    self::assertSame('https://mail.example.com', $parameters['%app_url%']);
                    self::assertSame('Example Mail', $parameters['%project_name%']);
                    self::assertSame('user@example.org', $parameters['%email%']);
                    self::assertArrayHasKey('%time%', $parameters);
                }
                if ('mail.recovery-subject' === $id) {
                    self::assertSame('user@example.org', $parameters['%email%']);
                }

                return 'translated';
            });

        $handler = $this->createStub(MailHandler::class);

        $mailer = new RecoveryProcessMailer($handler, $translator, $settingsService);
        $mailer->send($user, 'en');
    }

    public function testSendPassesResetLinkToTranslator(): void
    {
        $user = new User('user@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable('2026-01-15 10:00:00'));

        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')
            ->willReturnMap([
                ['app_url', null, 'https://mail.example.com/'],
                ['project_name', null, 'Example Mail'],
            ]);

        $capturedResetLink = null;
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static function (string $id, array $parameters) use (&$capturedResetLink): string {
                if ('mail.recovery-body' === $id) {
                    self::assertArrayHasKey('%reset_link%', $parameters);
                    $capturedResetLink = $parameters['%reset_link%'];
                }

                return 'translated';
            });

        $handler = $this->createStub(MailHandler::class);

        $mailer = new RecoveryProcessMailer($handler, $translator, $settingsService);
        $mailer->send($user, 'en');

        self::assertSame(
            'https://mail.example.com/account/recovery-token',
            $capturedResetLink,
            'Reset link should point at the account recovery-token page (login + password invalidates the old token and stops the recovery process)',
        );
    }
}
