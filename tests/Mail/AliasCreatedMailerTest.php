<?php

declare(strict_types=1);

namespace App\Tests\Mail;

use App\Entity\Alias;
use App\Entity\User;
use App\Handler\MailHandler;
use App\Mail\AliasCreatedMailer;
use App\Service\SettingsService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AliasCreatedMailerTest extends TestCase
{
    public function testSendCallsTranslatorAndMailHandler(): void
    {
        $user = new User('user@example.org');
        $alias = new Alias();
        $alias->setSource('alias@example.org');

        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturnMap([
            ['project_name', null, 'Example Mail'],
        ]);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static fn (string $id) => match ($id) {
                'mail.alias-created-body' => 'Alias body',
                'mail.alias-created-subject' => 'Alias subject',
            });

        $handler = $this->createMock(MailHandler::class);
        $handler->expects(self::once())
            ->method('send')
            ->with('user@example.org', 'Alias body', 'Alias subject');

        $mailer = new AliasCreatedMailer($handler, $translator, $settingsService, $this->createStub(UrlGeneratorInterface::class));
        $mailer->send($user, $alias, 'en');
    }

    public function testSendPassesLocaleToTranslator(): void
    {
        $user = new User('user@example.org');
        $alias = new Alias();
        $alias->setSource('alias@example.org');

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

        $mailer = new AliasCreatedMailer($handler, $translator, $settingsService, $this->createStub(UrlGeneratorInterface::class));
        $mailer->send($user, $alias, 'de');
    }

    public function testSendPassesCorrectParametersToTranslator(): void
    {
        $user = new User('user@example.org');
        $alias = new Alias();
        $alias->setSource('alias@example.org');

        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturnMap([
            ['project_name', null, 'Example Mail'],
        ]);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')
            ->with('aliases', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://mail.example.com/account/alias');

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::exactly(2))
            ->method('trans')
            ->willReturnCallback(static function (string $id, array $parameters) {
                if ('mail.alias-created-body' === $id) {
                    self::assertSame('Example Mail', $parameters['%project_name%']);
                    self::assertSame('user@example.org', $parameters['%email%']);
                    self::assertSame('alias@example.org', $parameters['%alias%']);
                    self::assertSame('https://mail.example.com/account/alias', $parameters['%alias_url%']);
                }
                if ('mail.alias-created-subject' === $id) {
                    self::assertSame('user@example.org', $parameters['%email%']);
                }

                return 'translated';
            });

        $handler = $this->createStub(MailHandler::class);

        $mailer = new AliasCreatedMailer($handler, $translator, $settingsService, $urlGenerator);
        $mailer->send($user, $alias, 'en');
    }
}
