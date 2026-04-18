<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Handler\MailHandler;
use App\Mail\RecoveryProcessMailer;
use App\Service\SettingsService;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Guards the route-name contract the mailers rely on. If someone renames
 * a route or changes a path without updating the mailer, this fails —
 * instead of silently shipping broken links in notification mails (see #97).
 */
class MailerRoutesTest extends KernelTestCase
{
    public function testMailerRouteNamesResolveToExpectedPaths(): void
    {
        self::bootKernel();
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);

        self::assertSame('/recovery', $urlGenerator->generate('recovery'));
        self::assertSame('/account/recovery-token', $urlGenerator->generate('account_recovery_token'));
        self::assertSame('/account/voucher', $urlGenerator->generate('vouchers'));
        self::assertSame('/account/alias', $urlGenerator->generate('aliases'));
    }

    /**
     * Messenger workers build mails without an HTTP Request on the stack.
     * Boots the kernel but does NOT create a client, so no Request exists
     * — the same shape as a worker. If the mailer ever switched to
     * ABSOLUTE_URL (needs a host) this would throw.
     */
    public function testMailerSendWorksWithoutHttpRequest(): void
    {
        self::bootKernel();
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);

        $settings = $this->createStub(SettingsService::class);
        $settings->method('get')->willReturnMap([
            ['app_url', null, 'https://mail.example.com'],
            ['project_name', null, 'Test'],
        ]);
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('translated');

        $user = new User('user@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable('2026-01-15 10:00:00'));

        $mailer = new RecoveryProcessMailer($this->createStub(MailHandler::class), $translator, $settings, $urlGenerator);
        $mailer->send($user, 'en');

        self::assertTrue(true, 'send() must not throw when no Request is on the stack');
    }
}
