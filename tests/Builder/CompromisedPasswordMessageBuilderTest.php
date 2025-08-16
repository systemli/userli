<?php

declare(strict_types=1);

namespace App\Tests\Builder;

use App\Builder\CompromisedPasswordMessageBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class CompromisedPasswordMessageBuilderTest extends TestCase
{
    private TranslatorInterface $translator;
    private CompromisedPasswordMessageBuilder $builder;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->builder = new CompromisedPasswordMessageBuilder(
            $this->translator,
            'https://example.org',
            'TestProject'
        );
    }

    public function testBuildBody(): void
    {
        $email = 'test@example.org';
        $locale = 'en';
        $expectedMessage = 'Hello test@example.org, your password may be compromised...';

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'mail.compromised-password-body',
                [
                    '%email%' => $email,
                    '%app_url%' => 'https://example.org',
                    '%project_name%' => 'TestProject',
                ],
                null,
                $locale
            )
            ->willReturn($expectedMessage);

        $result = $this->builder->buildBody($locale, $email);

        $this->assertEquals($expectedMessage, $result);
    }

    public function testBuildSubject(): void
    {
        $locale = 'en';
        $expectedSubject = 'Security Alert - Your password may be compromised';

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'mail.compromised-password-subject',
                [
                    '%project_name%' => 'TestProject',
                ],
                null,
                $locale
            )
            ->willReturn($expectedSubject);

        $result = $this->builder->buildSubject($locale);

        $this->assertEquals($expectedSubject, $result);
    }

    public function testBuildBodyWithDifferentLocale(): void
    {
        $email = 'benutzer@example.org';
        $locale = 'de';
        $expectedMessage = 'Hallo benutzer@example.org, Ihr Passwort könnte kompromittiert sein...';

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'mail.compromised-password-body',
                [
                    '%email%' => $email,
                    '%app_url%' => 'https://example.org',
                    '%project_name%' => 'TestProject',
                ],
                null,
                $locale
            )
            ->willReturn($expectedMessage);

        $result = $this->builder->buildBody($locale, $email);

        $this->assertEquals($expectedMessage, $result);
    }

    public function testBuildSubjectWithDifferentLocale(): void
    {
        $locale = 'de';
        $expectedSubject = 'Sicherheitswarnung - Ihr Passwort könnte kompromittiert sein';

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'mail.compromised-password-subject',
                [
                    '%project_name%' => 'TestProject',
                ],
                null,
                $locale
            )
            ->willReturn($expectedSubject);

        $result = $this->builder->buildSubject($locale);

        $this->assertEquals($expectedSubject, $result);
    }
}
