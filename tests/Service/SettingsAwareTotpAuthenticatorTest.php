<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\SettingsAwareTotpAuthenticator;
use App\Service\SettingsService;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;

class SettingsAwareTotpAuthenticatorTest extends TestCase
{
    private Stub&TotpAuthenticatorInterface $decoratedAuthenticator;
    private Stub&SettingsService $settingsService;
    private SettingsAwareTotpAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->decoratedAuthenticator = $this->createStub(TotpAuthenticatorInterface::class);
        $this->settingsService = $this->createStub(SettingsService::class);
        $this->authenticator = new SettingsAwareTotpAuthenticator(
            $this->decoratedAuthenticator,
            $this->settingsService
        );
    }

    public function testGetQRContentWithCustomSettings(): void
    {
        // Mock the original QR content from the decorated authenticator
        $originalQrContent = 'otpauth://totp/test%40example.com?secret=JBSWY3DPEHPK3PXP&issuer=DefaultIssuer';
        $user = $this->createStub(TwoFactorInterface::class);

        $this->decoratedAuthenticator
            ->method('getQRContent')
            ->willReturn($originalQrContent);

        // Mock settings service to return custom project name
        $this->settingsService
            ->method('get')
            ->willReturn('My Custom Project');

        $result = $this->authenticator->getQRContent($user);

        // Verify the result contains the custom project name
        self::assertStringContainsString('issuer=My+Custom+Project', $result);
        self::assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $result);
    }

    public function testGetQRContentWithDefaultSettings(): void
    {
        // Mock the original QR content from the decorated authenticator
        $originalQrContent = 'otpauth://totp/test%40example.com?secret=JBSWY3DPEHPK3PXP&issuer=DefaultIssuer';
        $user = $this->createStub(TwoFactorInterface::class);

        $this->decoratedAuthenticator
            ->method('getQRContent')
            ->willReturn($originalQrContent);

        // Mock settings service to return project name
        $this->settingsService
            ->method('get')
            ->willReturn('Userli');

        $result = $this->authenticator->getQRContent($user);

        // Verify the result contains the project name
        self::assertStringContainsString('issuer=Userli', $result);
        self::assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $result);
    }

    public function testGetQRContentHandlesUrlWithoutQuery(): void
    {
        // Test case where original QR content has no query parameters
        $originalQrContent = 'otpauth://totp/test%40example.com';
        $user = $this->createStub(TwoFactorInterface::class);

        $this->decoratedAuthenticator
            ->method('getQRContent')
            ->willReturn($originalQrContent);

        $this->settingsService
            ->method('get')
            ->willReturn('Test Project');

        $result = $this->authenticator->getQRContent($user);

        // Should add the issuer parameter
        self::assertStringContainsString('issuer=Test+Project', $result);
    }

    public function testGenerateSecretDelegatesToDecoratedAuthenticator(): void
    {
        $expectedSecret = 'JBSWY3DPEHPK3PXP';
        $this->decoratedAuthenticator
            ->method('generateSecret')
            ->willReturn($expectedSecret);

        $result = $this->authenticator->generateSecret();

        self::assertEquals($expectedSecret, $result);
    }

    public function testCheckCodeDelegatesToDecoratedAuthenticator(): void
    {
        $user = $this->createStub(TwoFactorInterface::class);
        $code = '123456';

        $this->decoratedAuthenticator
            ->method('checkCode')
            ->willReturn(true);

        $result = $this->authenticator->checkCode($user, $code);

        self::assertTrue($result);
    }

    public function testCheckCodeReturnsFalseWhenInvalid(): void
    {
        $user = $this->createStub(TwoFactorInterface::class);
        $code = '000000';

        $this->decoratedAuthenticator
            ->method('checkCode')
            ->willReturn(false);

        $result = $this->authenticator->checkCode($user, $code);

        self::assertFalse($result);
    }
}
