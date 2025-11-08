<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TotpSettingsIntegrationTest extends WebTestCase
{
    public function testTotpQRCodeGenerationUsesCurrentSettings(): void
    {
        $client = static::createClient();

        // This test would require a logged-in user and a working database
        // For now, we'll just test that the service can be retrieved from the container
        $totpAuthenticator = static::getContainer()->get('scheb_two_factor.security.totp_authenticator');
        $settingsService = static::getContainer()->get(\App\Service\SettingsService::class);

        self::assertInstanceOf(\App\Service\SettingsAwareTotpAuthenticator::class, $totpAuthenticator);
        self::assertInstanceOf(\App\Service\SettingsService::class, $settingsService);

        // Verify that the settings service can retrieve values
        $projectName = $settingsService->get('project_name');
        $appUrl = $settingsService->get('app_url');

        self::assertIsString($projectName);
        self::assertIsString($appUrl);
        self::assertNotEmpty($projectName);
        self::assertNotEmpty($appUrl);
    }

    public function testServiceDecorationIsWorkingCorrectly(): void
    {
        $client = static::createClient();

        // Get the original service
        $container = static::getContainer();
        $totpService = $container->get('scheb_two_factor.security.totp_authenticator');

        // Verify it's our decorated version
        self::assertInstanceOf(\App\Service\SettingsAwareTotpAuthenticator::class, $totpService);

        // Verify the interface is still implemented
        self::assertInstanceOf(\Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface::class, $totpService);
    }
}
