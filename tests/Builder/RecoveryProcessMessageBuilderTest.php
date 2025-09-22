<?php

declare(strict_types=1);

namespace App\Tests\Builder;

use App\Builder\RecoveryProcessMessageBuilder;
use App\Service\SettingsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class RecoveryProcessMessageBuilderTest extends TestCase
{
    private RecoveryProcessMessageBuilder $builder;
    private MockObject $translator;
    private MockObject $settingsService;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->settingsService = $this->createMock(SettingsService::class);

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

        $this->settingsService->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['app_url', null, $appUrl],
                ['project_name', null, $projectName],
            ]);

        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                'mail.recovery-body',
                [
                    '%app_url%' => $appUrl,
                    '%project_name%' => $projectName,
                    '%email%' => $email,
                    '%time%' => $time,
                ],
                null,
                $locale
            )
            ->willReturn($expectedBody);

        $result = $this->builder->buildBody($locale, $email, $time);

        self::assertEquals($expectedBody, $result);
    }

    public function testBuildSubject(): void
    {
        $locale = 'fr';
        $email = 'test@example.org';
        $expectedSubject = 'Processus de récupération pour test@example.org';

        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                'mail.recovery-subject',
                ['%email%' => $email],
                null,
                $locale
            )
            ->willReturn($expectedSubject);

        $result = $this->builder->buildSubject($locale, $email);

        self::assertEquals($expectedSubject, $result);
    }
}
