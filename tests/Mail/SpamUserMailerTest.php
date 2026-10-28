<?php

declare(strict_types=1);

namespace App\Tests\Mail;

use App\Entity\User;
use App\Handler\MailHandler;
use App\Mail\SpamUserMailer;
use App\Service\SettingsService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class SpamUserMailerTest extends TestCase
{
    public function testSendContainsAllLocalesAndSupportAddress(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static fn (string $id, array $parameters, ?string $domain, ?string $locale): string => match ($id) {
                'mail.spam-body' => $locale.' body '.$parameters['%support_address%'],
                'mail.spam-subject' => $locale.' subject',
                default => 'unexpected',
            });

        $handler = $this->createMock(MailHandler::class);
        $handler->expects(self::once())
            ->method('send')
            ->with('user@example.org', "en body support@example.org\n\n--\n\nde body support@example.org\n", 'en subject / de subject');

        new SpamUserMailer($handler, $translator, $this->settings('support@example.org'))->send(new User('user@example.org'));
    }

    public function testSendUsesFallbackBodyWithoutSupportAddress(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static fn (string $id): string => match ($id) {
                'mail.spam-body-no-support' => 'no support',
                'mail.spam-subject' => 'subject',
                default => 'unexpected',
            });

        $handler = $this->createMock(MailHandler::class);
        $handler->expects(self::once())
            ->method('send')
            ->with('user@example.org', "no support\n\n--\n\nno support\n", 'subject / subject');

        new SpamUserMailer($handler, $translator, $this->settings(''))->send(new User('user@example.org'));
    }

    private function settings(string $supportAddress): SettingsService
    {
        $settings = $this->createStub(SettingsService::class);
        $settings->method('get')->willReturnMap([
            ['project_name', null, 'Test Project'],
            ['support_address', null, $supportAddress],
        ]);

        return $settings;
    }
}
