<?php

namespace App\Controller\Api;

use App\Dto\PasswordDto;
use App\Dto\TwoFactorDto;
use App\Entity\User;
use App\Handler\MailCryptKeyHandler;
use App\Handler\DeleteHandler;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;

class TwoFactorController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
    ) {
    }

    #[Route('/api/user/twofactor', name: 'get_two_factor', methods: ['GET'], stateless: true)]
    public function getTwoFactor(
        #[CurrentUser] User $user,
    ): JsonResponse {
        return $this->json([
            'status' => 'success',
            'totp_enabled' => $user->isTotpAuthenticationEnabled(),
        ], 200);
    }

    /**
     * Delegates password validation to Password
     */
    #[Route('/api/user/twofactor', name: 'enable_two_factor', methods: ['POST'], stateless: true)]
    public function enableTwoFactor(
        TotpAuthenticatorInterface $totpAuthenticator,
        #[CurrentUser] User $user,
        #[MapRequestPayload] PasswordDto $request
    ): JsonResponse {
        if (true === $user->isTotpAuthenticationEnabled()) {
            return $this->json([
                'status' => 'error',
                'message' => 'nothing to do: totp is enabled.'
            ], 404);
        }

        $user->setTotpSecret($totpAuthenticator->generateSecret());
        $user->generateBackupCodes();
        $this->manager->flush();

        return $this->json([
            'status' => 'success',
            'qrcode' => $totpAuthenticator->getQRContent($user)
        ], 200);
    }

    /**
     * Delegates password validation to TwoFactorConfirm
     */
    #[Route('/api/user/twofactor/confirm', name: 'confirm_two_factor', methods: ['POST'], stateless: true)]
    public function confirmTwoFactor(
        #[CurrentUser] User $user,
        #[MapRequestPayload] TwoFactorDto $request
    ): JsonResponse {
        if (true === $user->isTotpAuthenticationEnabled()) {
            return $this->json([
                'status' => 'error',
                'message' => 'nothing to do: totp is enabled.'
            ], 404);
        }

        $user->setTotpConfirmed(true);
        $this->manager->flush();

        return $this->json([
            'status' => 'success',
            'twoFactorBackupCodes' => $user->getBackupCodes()
        ], 200);

        return $this->json(['status' => 'success'], 200);
    }

    /**
     * Delegates password validation to Password
     */
    #[Route('/api/user/twofactor', name: 'delete_two_factor', methods: ['DELETE'], stateless: true)]
    public function deleteTwoFactor(
        #[CurrentUser] User $user,
        #[MapRequestPayload] PasswordDto $request
    ): JsonResponse {
        if (false === $user->isTotpAuthenticationEnabled()) {
            return $this->json([
                'status' => 'error',
                'message' => 'nothing to do: totp is not enabled.'
            ], 404);
        }

        $user->setTotpConfirmed(false);
        $user->setTotpSecret(null);
        $this->manager->flush();

        return $this->json(['status' => 'success'], 200);
    }
}
