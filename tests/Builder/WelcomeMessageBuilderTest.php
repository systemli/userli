<?php

declare(strict_types=1);

namespace App\Tests\Builder;

use App\Builder\WelcomeMessageBuilder;
use App\Service\SettingsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class WelcomeMessageBuilderTest extends TestCase
{
    private MockObject $translator;
    private MockObject $settingsService;
    private WelcomeMessageBuilder $builder;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->settingsService = $this->createMock(SettingsService::class);

        $this->builder = new WelcomeMessageBuilder(
            $this->translator,
            $this->settingsService
        );
    }

    public function testBuildBody(): void
    {
        $locale = 'de';
        $appUrl = 'https://www.example.com';
        $projectName = 'Test Project';
        $expectedBody = 'Welcome to Test Project! Visit: https://www.example.com';

        // Settings service should always return valid values
        $this->settingsService->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['app_url', null, $appUrl],
                ['project_name', null, $projectName],
            ]);

        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                'mail.welcome-body',
                [
                    '%app_url%' => $appUrl,
                    '%project_name%' => $projectName,
                ],
                null,
                $locale
            )
            ->willReturn($expectedBody);

        $result = $this->builder->buildBody($locale);

        self::assertEquals($expectedBody, $result);
    }

    public function testBuildSubject(): void
    {
        $locale = 'en';
        $projectName = 'Test Project';
        $expectedSubject = 'Welcome to Test Project';

        // Settings service should always return valid project_name
        $this->settingsService->expects(self::once())
            ->method('get')
            ->with('project_name')
            ->willReturn($projectName);

        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                'mail.welcome-subject',
                ['%project_name%' => $projectName],
                null,
                $locale
            )
            ->willReturn($expectedSubject);

        $result = $this->builder->buildSubject($locale);

        self::assertEquals($expectedSubject, $result);
    }
}
