<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
}
