<?php

declare(strict_types=1);

namespace App\Security\TwoFactor;

use App\Entity\User;
use App\Service\WebauthnService;
use Override;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Second factor with WebAuthn security keys. The browser response is submitted as auth code.
 */
#[AutoconfigureTag('scheb_two_factor.provider', ['alias' => self::ALIAS])]
final readonly class WebauthnTwoFactorProvider implements TwoFactorProviderInterface
{
    public const string ALIAS = 'webauthn';

    public function __construct(
        private WebauthnService $webauthnService,
        private WebauthnFormRenderer $formRenderer,
    ) {
    }

    #[Override]
    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        $user = $context->getUser();

        return $user instanceof User && $user->isWebauthnAuthenticationEnabled();
    }

    #[Override]
    public function prepareAuthentication(object $user): void
    {
        // The challenge is created each time the form is rendered
    }

    #[Override]
    public function validateAuthenticationCode(object $user, string $authenticationCode): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        return $this->webauthnService->verifyAuthentication($user, $authenticationCode);
    }

    #[Override]
    public function getFormRenderer(): TwoFactorFormRendererInterface
    {
        return $this->formRenderer;
    }
}
