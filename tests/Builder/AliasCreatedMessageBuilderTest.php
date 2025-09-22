<?php

declare(strict_types=1);

namespace App\Tests\Builder;

use App\Builder\AliasCreatedMessageBuilder;
use App\Service\SettingsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class AliasCreatedMessageBuilderTest extends TestCase
{
    private AliasCreatedMessageBuilder $builder;
    private MockObject $translator;
    private MockObject $settingsService;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->settingsService = $this->createMock(SettingsService::class);

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

        $this->settingsService->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['app_url', null, $appUrl],
                ['project_name', null, $projectName],
            ]);

        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                'mail.alias-created-body',
                [
                    '%app_url%' => $appUrl,
                    '%project_name%' => $projectName,
                    '%email%' => $email,
                    '%alias%' => $alias,
                ],
                null,
                $locale
            )
            ->willReturn($expectedBody);

        $result = $this->builder->buildBody($locale, $email, $alias);

        self::assertEquals($expectedBody, $result);
    }

    public function testBuildSubject(): void
    {
        $locale = 'de';
        $email = 'test@example.org';
        $expectedSubject = 'Alias erstellt für test@example.org';

        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                'mail.alias-created-subject',
                ['%email%' => $email],
                null,
                $locale
            )
            ->willReturn($expectedSubject);

        $result = $this->builder->buildSubject($locale, $email);

        self::assertEquals($expectedSubject, $result);
    }
}
