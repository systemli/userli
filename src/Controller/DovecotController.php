<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\DovecotPassdbDto;
use App\Entity\User;
use App\Enum\ApiScope;
use App\Enum\MailCrypt;
use App\Enum\Roles;
use App\Handler\MailCryptKeyHandler;
use App\Handler\UserAuthenticationHandler;
use App\Security\RequireApiScope;
use Exception;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[RequireApiScope(scope: ApiScope::DOVECOT)]
final class DovecotController extends AbstractController
{
    public const MESSAGE_SUCCESS = 'success';

    public const MESSAGE_AUTHENTICATION_FAILED = 'authentication failed';

    public const MESSAGE_USER_NOT_FOUND = 'user not found';

    public const MESSAGE_USER_DISABLED = 'user disabled due to spam role';

    public const MESSAGE_USER_PASSWORD_CHANGE_REQUIRED = 'user password change required';

    private readonly MailCrypt $mailCrypt;

    public function __construct(
        private readonly MailCryptKeyHandler $mailCryptKeyHandler,
        private readonly UserAuthenticationHandler $authHandler,
        #[Autowire(env: 'MAIL_CRYPT')]
        private readonly int $mailCryptEnv,
    ) {
        $this->mailCrypt = MailCrypt::from($this->mailCryptEnv);
    }

    #[Route('/api/dovecot/status', name: 'api_dovecot_status', methods: ['GET'], stateless: true)]
    public function status(): JsonResponse
    {
        return $this->json([
            'message' => self::MESSAGE_SUCCESS,
        ], Response::HTTP_OK);
    }

    #[Route('/api/dovecot/{email}', name: 'api_dovecot_user_lookup', methods: ['GET'], stateless: true)]
    public function lookup(
        #[MapEntity(mapping: ['email' => 'email'])] User $user,
    ): JsonResponse {
        if ($user->isDeleted()) {
            return $this->json(['message' => self::MESSAGE_USER_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        if (
            $this->mailCrypt->isAtLeast(MailCrypt::ENABLED_OPTIONAL)
            && $user->getMailCryptEnabled()
            && $user->hasMailCryptPublicKey()
        ) {
            $mailCryptReported = 2;
        } else {
            $mailCryptReported = 0;
        }

        return $this->json(
            [
                'message' => self::MESSAGE_SUCCESS,
                'body' => [
                    'user' => $user->getEmail(),
                    'mailCrypt' => $mailCryptReported,
                    'mailCryptPublicKey' => $user->getMailCryptPublicKey() ?? '',
                    'quota' => $user->getQuota() !== null
                            ? sprintf('%dM', $user->getQuota())
                            : '',
                ],
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/api/dovecot/{email}', name: 'api_dovecot_user_authenticate', methods: ['POST'], stateless: true)]
    public function authenticate(
        #[MapEntity(mapping: ['email' => 'email'])] User $user,
        #[MapRequestPayload] DovecotPassdbDto $request,
    ): JsonResponse {
        if ($user->isDeleted()) {
            return $this->json(['message' => self::MESSAGE_USER_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        if ($user->hasRole(Roles::SPAM)) {
            return $this->json(['message' => self::MESSAGE_USER_DISABLED], Response::HTTP_FORBIDDEN);
        }

        if ($user->isPasswordChangeRequired()) {
            return $this->json(['message' => self::MESSAGE_USER_PASSWORD_CHANGE_REQUIRED], Response::HTTP_FORBIDDEN);
        }

        if (null === $this->authHandler->authenticate($user, $request->getPassword())) {
            return $this->json(['message' => self::MESSAGE_AUTHENTICATION_FAILED], Response::HTTP_UNAUTHORIZED);
        }

        // If mailCrypt is enabled and enabled for user, derive mailCryptPrivateKey
        if ($this->mailCrypt->isAtLeast(MailCrypt::ENABLED_OPTIONAL) && $user->getMailCryptEnabled()) {
            try {
                $privateKey = $this->mailCryptKeyHandler->decrypt($user, $request->getPassword());
            } catch (Exception $exception) {
                return $this->json(['error' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $mailCryptReported = 2;
        } else {
            $mailCryptReported = 0;
        }

        return $this->json([
            'message' => self::MESSAGE_SUCCESS,
            'body' => [
                'mailCrypt' => $mailCryptReported,
                'mailCryptPrivateKey' => $privateKey ?? '',
                'mailCryptPublicKey' => $user->getMailCryptPublicKey() ?? '',
            ],
        ], Response::HTTP_OK);
    }
}
