<?php

declare(strict_types=1);

namespace App\Tests\Mail;

use App\Entity\Domain;
use App\Entity\User;
use App\Handler\MailHandler;
use App\Mail\WelcomeMailer;
use App\Service\SettingsService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class WelcomeMailerTest extends TestCase
{
    public function testSendCallsTranslatorAndMailHandler(): void
    {
        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturnMap([
            ['app_url', null, 'https://www.example.com'],
            ['project_name', null, 'Test Project'],
        ]);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static fn (string $id) => match ($id) {
                'mail.welcome-body' => 'Welcome body',
                'mail.welcome-subject' => 'Welcome subject',
                default => 'part',
            });

        $handler = $this->createMock(MailHandler::class);
        $handler->expects(self::once())
            ->method('send')
            ->with('user@example.org', 'Welcome body', 'Welcome subject');

        $mailer = new WelcomeMailer($handler, $translator, $settingsService, $this->createStub(UrlGeneratorInterface::class));
        $mailer->send(new User('user@example.org'), 'en');
    }

    public function testSendPassesLocaleToTranslator(): void
    {
        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturn('value');

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::exactly(3))
            ->method('trans')
            ->willReturnCallback(static function (string $id, array $parameters, ?string $domain, ?string $locale): string {
                self::assertSame('de', $locale);

                return 'translated';
            });

        $handler = $this->createMock(MailHandler::class);
        $handler->expects(self::once())->method('send');

        $mailer = new WelcomeMailer($handler, $translator, $settingsService, $this->createStub(UrlGeneratorInterface::class));
        $mailer->send(new User('user@example.org'), 'de');
    }

    public function testSendBuildsVoucherUrlFromAppUrlSettingAndRoutePath(): void
    {
        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturnMap([
            ['app_url', null, 'https://www.example.com'],
            ['project_name', null, 'Test Project'],
        ]);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('vouchers', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/account/voucher');

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::exactly(3))
            ->method('trans')
            ->willReturnCallback(static function (string $id, array $parameters) {
                if ('mail.welcome-body' === $id) {
                    self::assertSame('Test Project', $parameters['%project_name%']);
                    self::assertSame('https://www.example.com/account/voucher', $parameters['%voucher_url%']);
                }
                if ('mail.welcome-subject' === $id) {
                    self::assertSame('Test Project', $parameters['%project_name%']);
                }

                return 'translated';
            });

        $handler = $this->createStub(MailHandler::class);

        $mailer = new WelcomeMailer($handler, $translator, $settingsService, $urlGenerator);
        $mailer->send(new User('user@example.org'), 'en');
    }

    public function testSendPassesQuotaAndInvitationPartsToBody(): void
    {
        $domain = new Domain();
        $domain->getInvitationSettings()->setEnabled(true);
        $domain->getInvitationSettings()->setLimit(5);
        $user = new User('user@example.org');
        $user->setDomain($domain);
        $user->setQuota(2048);

        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturn('value');

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/account/voucher');

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static fn (string $id, array $parameters) => match ($id) {
                'mail.welcome-quota' => 'quota '.$parameters['%quota%'],
                'mail.welcome-invitations' => 'invites '.$parameters['%limit%'],
                'mail.welcome-body' => $parameters['%quota_text%'].'|'.$parameters['%invitation_text%'],
                default => 'subject',
            });

        $handler = $this->createMock(MailHandler::class);
        $handler->expects(self::once())->method('send')->with('user@example.org', 'quota 2048|invites 5', 'subject');

        new WelcomeMailer($handler, $translator, $settingsService, $urlGenerator)->send($user, 'en');
    }

    public function testSendOmitsInvitationsWhenDisabledAndUsesUnlimitedQuota(): void
    {
        $user = new User('user@example.org');
        $user->setDomain(new Domain());

        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturn('value');

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/account/voucher');

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static fn (string $id, array $parameters) => match ($id) {
                'mail.welcome-quota-unlimited' => 'unlimited',
                'mail.welcome-body' => "a\n\n".$parameters['%invitation_text%']."\n\nb",
                default => 'subject',
            });

        $handler = $this->createMock(MailHandler::class);
        $handler->expects(self::once())->method('send')->with('user@example.org', "a\n\nb", 'subject');

        new WelcomeMailer($handler, $translator, $settingsService, $urlGenerator)->send($user, 'en');
    }
}
