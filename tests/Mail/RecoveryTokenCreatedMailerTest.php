<?php

declare(strict_types=1);

namespace App\Tests\Mail;

use App\Entity\User;
use App\Handler\MailHandler;
use App\Mail\RecoveryTokenCreatedMailer;
use App\Service\SettingsService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RecoveryTokenCreatedMailerTest extends TestCase
{
    public function testSendBuildsMailWithLinksAndLocale(): void
    {
        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturnMap([
            ['app_url', null, 'https://mail.example.com/'],
            ['project_name', null, 'Example Mail'],
        ]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnMap([
            ['account_password', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/account/password'],
            ['account_recovery_token', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/account/recovery-token'],
        ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::exactly(2))
            ->method('trans')
            ->willReturnCallback(static function (string $id, array $parameters, ?string $domain, ?string $locale): string {
                self::assertSame('de', $locale);

                if ('mail.recovery-token-created-body' === $id) {
                    self::assertSame('Example Mail', $parameters['%project_name%']);
                    self::assertSame('user@example.org', $parameters['%email%']);
                    self::assertSame('https://mail.example.com/account/password', $parameters['%password_url%']);
                    self::assertSame('https://mail.example.com/account/recovery-token', $parameters['%recovery_token_url%']);

                    return 'Token body';
                }

                self::assertSame('mail.recovery-token-created-subject', $id);
                self::assertSame('user@example.org', $parameters['%email%']);

                return 'Token subject';
            });

        $handler = $this->createMock(MailHandler::class);
        $handler->expects(self::once())
            ->method('send')
            ->with('user@example.org', 'Token body', 'Token subject');

        $mailer = new RecoveryTokenCreatedMailer($handler, $translator, $settingsService, $urlGenerator);
        $mailer->send(new User('user@example.org'), 'de');
    }
}
