<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\SettingsAwareTotpAuthenticator;
use App\Service\SettingsService;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;

class SettingsAwareTotpAuthenticatorTest extends TestCase
{
    private TotpAuthenticatorInterface $decoratedAuthenticator;
    private SettingsService $settingsService;
    private SettingsAwareTotpAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->decoratedAuthenticator = $this->createMock(TotpAuthenticatorInterface::class);
        $this->settingsService = $this->createMock(SettingsService::class);
        $this->authenticator = new SettingsAwareTotpAuthenticator(
            $this->decoratedAuthenticator,
            $this->settingsService
        );
    }

    public function testGetQRContentWithCustomSettings(): void
    {
        // Mock the original QR content from the decorated authenticator
        $originalQrContent = 'otpauth://totp/test%40example.com?secret=JBSWY3DPEHPK3PXP&issuer=DefaultIssuer';
        $user = $this->createMock(TwoFactorInterface::class);

        $this->decoratedAuthenticator
            ->expects(self::once())
            ->method('getQRContent')
            ->with($user)
            ->willReturn($originalQrContent);

        // Mock settings service to return custom project name
        $this->settingsService
            ->expects(self::once())
            ->method('get')
            ->with('project_name')
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
        $user = $this->createMock(TwoFactorInterface::class);

        $this->decoratedAuthenticator
            ->expects(self::once())
            ->method('getQRContent')
            ->with($user)
            ->willReturn($originalQrContent);

        // Mock settings service to return project name
        $this->settingsService
            ->expects(self::once())
            ->method('get')
            ->with('project_name')
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
        $user = $this->createMock(TwoFactorInterface::class);

        $this->decoratedAuthenticator
            ->expects(self::once())
            ->method('getQRContent')
            ->with($user)
            ->willReturn($originalQrContent);

        $this->settingsService
            ->expects(self::once())
            ->method('get')
            ->with('project_name')
            ->willReturn('Test Project');

        $result = $this->authenticator->getQRContent($user);

        // Should add the issuer parameter
        self::assertStringContainsString('issuer=Test+Project', $result);
    }

    public function testGenerateSecretDelegatesToDecoratedAuthenticator(): void
    {
        $expectedSecret = 'JBSWY3DPEHPK3PXP';
        $this->decoratedAuthenticator
            ->expects(self::once())
            ->method('generateSecret')
            ->willReturn($expectedSecret);

        $result = $this->authenticator->generateSecret();

        self::assertEquals($expectedSecret, $result);
    }

    public function testCheckCodeDelegatesToDecoratedAuthenticator(): void
    {
        $user = $this->createMock(TwoFactorInterface::class);
        $code = '123456';

        $this->decoratedAuthenticator
            ->expects(self::once())
            ->method('checkCode')
            ->with($user, $code)
            ->willReturn(true);

        $result = $this->authenticator->checkCode($user, $code);

        self::assertTrue($result);
    }

    public function testCheckCodeReturnsFalseWhenInvalid(): void
    {
        $user = $this->createMock(TwoFactorInterface::class);
        $code = '000000';

        $this->decoratedAuthenticator
            ->expects(self::once())
            ->method('checkCode')
            ->with($user, $code)
            ->willReturn(false);

        $result = $this->authenticator->checkCode($user, $code);

        self::assertFalse($result);
    }
}
