<?php

declare(strict_types=1);

namespace App\Tests\Mail;

use App\Entity\User;
use App\Handler\MailHandler;
use App\Mail\RecoveryProcessMailer;
use App\Repository\UserRepository;
use App\Service\RecoveryCancelLinkService;
use App\Service\SettingsService;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RecoveryProcessMailerTest extends TestCase
{
    public function testSendCallsTranslatorAndMailHandler(): void
    {
        $user = new User('user@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable('2026-01-15 10:00:00'));

        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturnMap([
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

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $mailer = new RecoveryProcessMailer($handler, $translator, $settingsService, $urlGenerator, $this->createCancelLinkService($urlGenerator));
        $mailer->send($user, 'en');
    }

    public function testSendThrowsWithoutRecoveryStartTime(): void
    {
        $handler = $this->createMock(MailHandler::class);
        $handler->expects(self::never())->method('send');

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $mailer = new RecoveryProcessMailer(
            $handler,
            $this->createStub(TranslatorInterface::class),
            $this->createStub(SettingsService::class),
            $urlGenerator,
            $this->createCancelLinkService($urlGenerator),
        );

        $this->expectException(LogicException::class);
        $mailer->send(new User('user@example.org'), 'en');
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

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $mailer = new RecoveryProcessMailer($handler, $translator, $settingsService, $urlGenerator, $this->createCancelLinkService($urlGenerator));
        $mailer->send($user, 'de');
    }

    public function testSendPassesExpectedPlaceholdersToTranslator(): void
    {
        $user = new User('user@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable('2026-01-15 10:00:00'));

        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturnMap([
            ['app_url', null, 'https://mail.example.com/'], // trailing slash gets normalised
            ['project_name', null, 'Example Mail'],
        ]);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(static fn (string $name, array $parameters) => match ($name) {
            'recovery' => '/recovery',
            'account_recovery_token' => '/account/recovery-token',
            'recovery_cancel' => '/recovery/cancel?'.http_build_query($parameters),
        });

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::exactly(2))
            ->method('trans')
            ->willReturnCallback(static function (string $id, array $parameters) {
                if ('mail.recovery-body' === $id) {
                    self::assertSame('Example Mail', $parameters['%project_name%']);
                    self::assertSame('user@example.org', $parameters['%email%']);
                    self::assertArrayHasKey('%time%', $parameters);
                    self::assertSame(48, $parameters['%hours%']);
                    self::assertSame('https://mail.example.com/recovery', $parameters['%recovery_url%']);
                    self::assertSame('https://mail.example.com/account/recovery-token', $parameters['%recovery_token_url%']);
                    self::assertStringStartsWith('https://mail.example.com/recovery/cancel?', $parameters['%cancel_url%']);
                    self::assertStringContainsString('_hash=', $parameters['%cancel_url%']);
                }
                if ('mail.recovery-subject' === $id) {
                    self::assertSame('user@example.org', $parameters['%email%']);
                }

                return 'translated';
            });

        $mailer = new RecoveryProcessMailer($this->createStub(MailHandler::class), $translator, $settingsService, $urlGenerator, $this->createCancelLinkService($urlGenerator));
        $mailer->send($user, 'en');
    }

    private function createCancelLinkService(UrlGeneratorInterface $urlGenerator): RecoveryCancelLinkService
    {
        return new RecoveryCancelLinkService(new UriSigner('secret'), $urlGenerator, $this->createStub(UserRepository::class));
    }
}
