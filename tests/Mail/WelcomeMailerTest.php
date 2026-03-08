<?php

declare(strict_types=1);

namespace App\Tests\Mail;

use App\Entity\User;
use App\Handler\MailHandler;
use App\Mail\WelcomeMailer;
use App\Service\SettingsService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class WelcomeMailerTest extends TestCase
{
    public function testSendCallsTranslatorAndMailHandler(): void
    {
        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')
            ->willReturnMap([
                ['app_url', null, 'https://www.example.com'],
                ['project_name', null, 'Test Project'],
            ]);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static fn (string $id) => match ($id) {
                'mail.welcome-body' => 'Welcome body',
                'mail.welcome-subject' => 'Welcome subject',
            });

        $handler = $this->createMock(MailHandler::class);
        $handler->expects(self::once())
            ->method('send')
            ->with('user@example.org', 'Welcome body', 'Welcome subject');

        $mailer = new WelcomeMailer($handler, $translator, $settingsService);
        $mailer->send(new User('user@example.org'), 'en');
    }

    public function testSendPassesLocaleToTranslator(): void
    {
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

        $mailer = new WelcomeMailer($handler, $translator, $settingsService);
        $mailer->send(new User('user@example.org'), 'de');
    }

    public function testSendPassesCorrectParametersToTranslator(): void
    {
        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')
            ->willReturnMap([
                ['app_url', null, 'https://www.example.com'],
                ['project_name', null, 'Test Project'],
            ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::exactly(2))
            ->method('trans')
            ->willReturnCallback(static function (string $id, array $parameters) {
                if ('mail.welcome-body' === $id) {
                    self::assertSame('https://www.example.com', $parameters['%app_url%']);
                    self::assertSame('Test Project', $parameters['%project_name%']);
                }
                if ('mail.welcome-subject' === $id) {
                    self::assertSame('Test Project', $parameters['%project_name%']);
                }

                return 'translated';
            });

        $handler = $this->createStub(MailHandler::class);

        $mailer = new WelcomeMailer($handler, $translator, $settingsService);
        $mailer->send(new User('user@example.org'), 'en');
    }
}
