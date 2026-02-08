<?php

declare(strict_types=1);

namespace App\Tests\Builder;

use App\Builder\RecoveryProcessMessageBuilder;
use App\Service\SettingsService;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class RecoveryProcessMessageBuilderTest extends TestCase
{
    private RecoveryProcessMessageBuilder $builder;
    private Stub&TranslatorInterface $translator;
    private Stub&SettingsService $settingsService;

    protected function setUp(): void
    {
        $this->translator = $this->createStub(TranslatorInterface::class);
        $this->settingsService = $this->createStub(SettingsService::class);

        $this->builder = new RecoveryProcessMessageBuilder(
            $this->translator,
            $this->settingsService
        );
    }

    public function testBuildBody(): void
    {
        $locale = 'en';
        $email = 'user@example.com';
        $time = '2025-09-18 10:30:00';
        $appUrl = 'https://mail.example.com';
        $projectName = 'Example Mail';
        $expectedBody = 'Recovery process initiated for user@example.com at 2025-09-18 10:30:00';

        $this->settingsService
            ->method('get')
            ->willReturnMap([
                ['app_url', null, $appUrl],
                ['project_name', null, $projectName],
            ]);

        $this->translator
            ->method('trans')
            ->willReturn($expectedBody);

        $result = $this->builder->buildBody($locale, $email, $time);

        self::assertEquals($expectedBody, $result);
    }

    public function testBuildSubject(): void
    {
        $locale = 'fr';
        $email = 'test@example.org';
        $expectedSubject = 'Processus de récupération pour test@example.org';

        $this->translator
            ->method('trans')
            ->willReturn($expectedSubject);

        $result = $this->builder->buildSubject($locale, $email);

        self::assertEquals($expectedSubject, $result);
    }
}
