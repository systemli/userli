<?php

declare(strict_types=1);

namespace App\Security\TwoFactor;

use App\Entity\User;
use App\Service\WebauthnService;
use Override;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final readonly class WebauthnFormRenderer implements TwoFactorFormRendererInterface
{
    public function __construct(
        private Environment $twig,
        private Security $security,
        private WebauthnService $webauthnService,
    ) {
    }

    /**
     * @param array<string,mixed> $templateVars
     */
    #[Override]
    public function renderForm(Request $request, array $templateVars): Response
    {
        $user = $this->security->getUser();
        assert($user instanceof User);

        return new Response($this->twig->render('Security/2fa_webauthn_form.html.twig', array_merge($templateVars, [
            'webauthnOptions' => $this->webauthnService->createAuthenticationOptions($user),
        ])));
    }
}
