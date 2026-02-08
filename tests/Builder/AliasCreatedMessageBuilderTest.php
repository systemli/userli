<?php

declare(strict_types=1);

namespace App\Tests\Builder;

use App\Builder\AliasCreatedMessageBuilder;
use App\Service\SettingsService;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class AliasCreatedMessageBuilderTest extends TestCase
{
    private AliasCreatedMessageBuilder $builder;
    private Stub&TranslatorInterface $translator;
    private Stub&SettingsService $settingsService;

    protected function setUp(): void
    {
        $this->translator = $this->createStub(TranslatorInterface::class);
        $this->settingsService = $this->createStub(SettingsService::class);

        $this->builder = new AliasCreatedMessageBuilder(
            $this->translator,
            $this->settingsService
        );
    }

    public function testBuildBody(): void
    {
        $locale = 'en';
        $email = 'user@example.com';
        $alias = 'alias@example.com';
        $appUrl = 'https://mail.example.com';
        $projectName = 'Example Mail';
        $expectedBody = 'Your alias alias@example.com has been created for user@example.com';

        $this->settingsService
            ->method('get')
            ->willReturnMap([
                ['app_url', null, $appUrl],
                ['project_name', null, $projectName],
            ]);

        $this->translator
            ->method('trans')
            ->willReturn($expectedBody);

        $result = $this->builder->buildBody($locale, $email, $alias);

        self::assertEquals($expectedBody, $result);
    }

    public function testBuildSubject(): void
    {
        $locale = 'de';
        $email = 'test@example.org';
        $expectedSubject = 'Alias erstellt für test@example.org';

        $this->translator
            ->method('trans')
            ->willReturn($expectedSubject);

        $result = $this->builder->buildSubject($locale, $email);

        self::assertEquals($expectedSubject, $result);
    }
}
