<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Alias;
use App\Entity\User;
use App\Handler\MailHandler;
use App\Mail\AliasCreatedMailer;
use App\Mail\RecoveryProcessMailer;
use App\Mail\WelcomeMailer;
use App\Service\SettingsService;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * These tests wire the mailers against the real UrlGenerator from the
 * container — proving the route names exist in the routing config and that
 * path generation works without a Request context (i.e. in messenger
 * background workers). Host comes from the admin-editable `app_url` setting,
 * not from the router's default_uri, so changing app_url also changes mail
 * links.
 */
class MailerUrlGenerationTest extends KernelTestCase
{
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
    }

    public function testRecoveryMailerBuildsExpectedLinks(): void
    {
        $capturedBody = null;
        $mailer = new RecoveryProcessMailer(
            $this->captureBodyHandler($capturedBody),
            $this->passthroughTranslator(),
            $this->appUrlSettingsStub('https://mail.example.com'),
            $this->urlGenerator,
        );

        $user = new User('user@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable('2026-01-15 10:00:00'));
        $mailer->send($user, 'en');

        self::assertStringContainsString('%recovery_url%=https://mail.example.com/recovery', $capturedBody);
        self::assertStringContainsString('%recovery_token_url%=https://mail.example.com/account/recovery-token', $capturedBody);
    }

    public function testWelcomeMailerBuildsExpectedLinks(): void
    {
        $capturedBody = null;
        $mailer = new WelcomeMailer(
            $this->captureBodyHandler($capturedBody),
            $this->passthroughTranslator(),
            $this->appUrlSettingsStub('https://mail.example.com'),
            $this->urlGenerator,
        );

        $mailer->send(new User('user@example.org'), 'en');

        self::assertStringContainsString('%voucher_url%=https://mail.example.com/account/voucher', $capturedBody);
    }

    public function testAliasCreatedMailerBuildsExpectedLinks(): void
    {
        $capturedBody = null;
        $alias = new Alias();
        $alias->setSource('alias@example.org');
        $mailer = new AliasCreatedMailer(
            $this->captureBodyHandler($capturedBody),
            $this->passthroughTranslator(),
            $this->appUrlSettingsStub('https://mail.example.com'),
            $this->urlGenerator,
        );

        $mailer->send(new User('user@example.org'), $alias, 'en');

        self::assertStringContainsString('%alias_url%=https://mail.example.com/account/alias', $capturedBody);
    }

    public function testTrailingSlashOnAppUrlIsNormalised(): void
    {
        $capturedBody = null;
        $mailer = new WelcomeMailer(
            $this->captureBodyHandler($capturedBody),
            $this->passthroughTranslator(),
            $this->appUrlSettingsStub('https://mail.example.com/'),
            $this->urlGenerator,
        );

        $mailer->send(new User('user@example.org'), 'en');

        self::assertStringContainsString('%voucher_url%=https://mail.example.com/account/voucher', $capturedBody);
        self::assertStringNotContainsString('https://mail.example.com//', $capturedBody);
    }

    private function captureBodyHandler(?string &$capturedBody): MailHandler
    {
        $handler = $this->createStub(MailHandler::class);
        $handler->method('send')
            ->willReturnCallback(static function (string $email, string $body) use (&$capturedBody): void {
                $capturedBody = $body;
            });

        return $handler;
    }

    /**
     * Returns a translator that renders every placeholder into `name=value` pairs so
     * tests can inspect what was passed without needing translation files.
     */
    private function passthroughTranslator(): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static function (string $id, array $parameters): string {
                $pairs = [];
                foreach ($parameters as $key => $value) {
                    $pairs[] = $key.'='.$value;
                }

                return $id.' '.implode(' ', $pairs);
            });

        return $translator;
    }

    private function appUrlSettingsStub(string $appUrl): SettingsService
    {
        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturnMap([
            ['app_url', null, $appUrl],
            ['project_name', null, 'Example Mail'],
        ]);

        return $settingsService;
    }
}
