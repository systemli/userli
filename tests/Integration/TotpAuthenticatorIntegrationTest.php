<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\User;
use App\Service\SettingsAwareTotpAuthenticator;
use App\Service\SettingsService;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TotpAuthenticatorIntegrationTest extends KernelTestCase
{
    private TotpAuthenticatorInterface $totpAuthenticator;
    private SettingsService $settingsService;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->totpAuthenticator = static::getContainer()->get(TotpAuthenticatorInterface::class);
        $this->settingsService = static::getContainer()->get(SettingsService::class);
    }

    public function testServiceIsDecoratedCorrectly(): void
    {
        // Verify that our custom authenticator is being used
        self::assertInstanceOf(SettingsAwareTotpAuthenticator::class, $this->totpAuthenticator);
    }

    public function testQRContentUsesRealSettings(): void
    {
        // Create a test user that implements TwoFactorInterface
        $user = $this->createTestUser();

        // Generate a secret first
        $secret = $this->totpAuthenticator->generateSecret();
        $user->setTotpSecret($secret);
        $user->setTotpConfirmed(true);

        // Get QR content
        $qrContent = $this->totpAuthenticator->getQRContent($user);

        // Verify it's a valid TOTP URL
        self::assertStringStartsWith('otpauth://totp/', $qrContent);

        // Get current project name from settings
        $projectName = $this->settingsService->get('project_name');

        // Verify the QR content contains the dynamic values
        self::assertStringContainsString('issuer='.urlencode($projectName), $qrContent);
        self::assertStringContainsString('secret='.$secret, $qrContent);
    }

    public function testSecretGenerationWorks(): void
    {
        $secret = $this->totpAuthenticator->generateSecret();

        self::assertIsString($secret);
        self::assertNotEmpty($secret);
        // TOTP secrets should be base32 encoded and have specific length
        self::assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function testCodeValidationWorks(): void
    {
        $user = $this->createTestUser();

        // Generate and set a secret
        $secret = $this->totpAuthenticator->generateSecret();
        $user->setTotpSecret($secret);
        $user->setTotpConfirmed(true);

        // Test with invalid code
        $isValid = $this->totpAuthenticator->checkCode($user, '000000');
        self::assertFalse($isValid);

        // Note: Testing with a valid code is tricky because it requires time-based calculation
        // In a real test, you might want to mock the time or use a known secret/time combination
    }

    public function testSettingsChangeAffectsQRContent(): void
    {
        $user = $this->createTestUser();
        $secret = $this->totpAuthenticator->generateSecret();
        $user->setTotpSecret($secret);
        $user->setTotpConfirmed(true);

        // Get initial QR content
        $initialQrContent = $this->totpAuthenticator->getQRContent($user);

        // Store original value for cleanup
        $originalProjectName = $this->settingsService->get('project_name');

        // Change settings
        $newProjectName = 'Test Project '.uniqid();
        $this->settingsService->set('project_name', $newProjectName);

        // Get QR content again
        $updatedQrContent = $this->totpAuthenticator->getQRContent($user);

        // Verify the content changed
        self::assertNotEquals($initialQrContent, $updatedQrContent);
        self::assertStringContainsString('issuer='.urlencode($newProjectName), $updatedQrContent);

        // Clean up: reset to original value
        $this->settingsService->set('project_name', $originalProjectName);
    }

    public function testQRContentWithDefaultSettings(): void
    {
        $user = $this->createTestUser();
        $secret = $this->totpAuthenticator->generateSecret();
        $user->setTotpSecret($secret);
        $user->setTotpConfirmed(true);

        // Get QR content
        $qrContent = $this->totpAuthenticator->getQRContent($user);

        // Should contain a valid TOTP URL structure
        self::assertStringStartsWith('otpauth://totp/', $qrContent);
        self::assertStringContainsString('secret='.$secret, $qrContent);

        // Should contain some issuer from settings
        self::assertStringContainsString('issuer=', $qrContent);
    }

    private function createTestUser(): User
    {
        // Create a minimal User instance for testing
        $user = new User('test@example.com');

        return $user;
    }
}
