<?php

declare(strict_types=1);

namespace App\Tests\Security\TwoFactor;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use App\Security\TwoFactor\WebauthnFormRenderer;
use App\Security\TwoFactor\WebauthnTwoFactorProvider;
use App\Service\WebauthnService;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use stdClass;

class WebauthnTwoFactorProviderTest extends TestCase
{
    public function testBeginAuthenticationOnlyWithSecurityKey(): void
    {
        $provider = new WebauthnTwoFactorProvider($this->createStub(WebauthnService::class), $this->createStub(WebauthnFormRenderer::class));
        $user = new User('user@example.org');

        self::assertFalse($provider->beginAuthentication($this->createContext($user)));

        $user->getWebauthnCredentials()->add(new WebauthnCredential($user, 'YubiKey', 'aWQ', 'a2V5', 'aGFuZGxl', 0, [], '00000000-0000-0000-0000-000000000000', 'none', false, false, false));

        self::assertTrue($provider->beginAuthentication($this->createContext($user)));
    }

    public function testValidateAuthenticationCodeVerifiesAssertion(): void
    {
        $user = new User('user@example.org');
        $service = $this->createMock(WebauthnService::class);
        $service->expects($this->once())->method('verifyAuthentication')->with($user, '{"id":"x"}')->willReturn(true);
        $provider = new WebauthnTwoFactorProvider($service, $this->createStub(WebauthnFormRenderer::class));

        self::assertTrue($provider->validateAuthenticationCode($user, '{"id":"x"}'));
    }

    public function testValidateAuthenticationCodeRejectsOtherUsers(): void
    {
        $service = $this->createMock(WebauthnService::class);
        $service->expects($this->never())->method('verifyAuthentication');
        $provider = new WebauthnTwoFactorProvider($service, $this->createStub(WebauthnFormRenderer::class));

        self::assertFalse($provider->validateAuthenticationCode(new stdClass(), '{"id":"x"}'));
    }

    private function createContext(User $user): AuthenticationContextInterface
    {
        $context = $this->createStub(AuthenticationContextInterface::class);
        $context->method('getUser')->willReturn($user);

        return $context;
    }
}
