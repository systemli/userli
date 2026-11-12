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
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static fn (string $id, array $parameters, ?string $domain, ?string $locale): string => match ($id) {
                'mail.announcement-footer' => $locale.' footer '.$parameters['%project_name%'],
                default => 'unexpected',
            });

        $settings = $this->createStub(SettingsService::class);
        $settings->method('get')->willReturnMap([['project_name', null, 'example.org']]);

        $handler = $this->createMock(MailHandler::class);
        $handler->expects(self::once())
            ->method('send')
            ->with(
                'user@example.org',
                "Hello\nworld\n\n-- \nen footer example.org\nde footer example.org\n",
                'Maintenance'
            );

        new AnnouncementMailer($handler, $translator, $settings)->send('user@example.org', 'Maintenance', "Hello\nworld\n\n");
    }
}
