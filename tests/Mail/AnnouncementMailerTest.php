<?php

declare(strict_types=1);

namespace App\Tests\Mail;

use App\Handler\MailHandler;
use App\Mail\AnnouncementMailer;
use App\Service\SettingsService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class AnnouncementMailerTest extends TestCase
{
    public function testSendAppendsFooterInAllLocales(): void
    {
        $handler = $this->createMock(MailHandler::class);
        $handler->expects(self::once())
            ->method('send')
            ->with(
                'user@example.org',
                "Hello\nworld\n\n-- \nen footer example.org\nde footer example.org\n",
                'Maintenance'
            );

        $this->createMailer($handler)->send('user@example.org', 'Maintenance', "Hello\nworld\n\n", null);
    }

    public function testBuildBodyNamesDomainForDomainAdmins(): void
    {
        self::assertSame(
            "Hello\n\n-- \nen domain footer example.com\nde domain footer example.com\n",
            $this->createMailer($this->createStub(MailHandler::class))->buildBody('Hello', 'example.com')
        );
    }

    private function createMailer(MailHandler $handler): AnnouncementMailer
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static fn (string $id, array $parameters, ?string $domain, ?string $locale): string => match ($id) {
                'mail.announcement-footer' => $locale.' footer '.$parameters['%project_name%'],
                'mail.announcement-footer-domain' => $locale.' domain footer '.$parameters['%domain%'],
                default => 'unexpected',
            });

        $settings = $this->createStub(SettingsService::class);
        $settings->method('get')->willReturnMap([['project_name', null, 'example.org']]);

        return new AnnouncementMailer($handler, $translator, $settings);
    }
}
