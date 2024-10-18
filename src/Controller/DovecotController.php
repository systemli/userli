<?php

namespace App\Controller;

use App\Dto\DovecotPassdbDto;
use App\Entity\User;
use App\Enum\MailCrypt;
use App\Enum\Roles;
use App\Handler\MailCryptKeyHandler;
use App\Handler\UserAuthenticationHandler;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Routing\Annotation\Route;

class DovecotController extends AbstractController
{
    const MESSAGE_SUCCESS = 'success';
    const MESSAGE_AUTHENTICATION_FAILED = 'authentication failed';
    const MESSAGE_USER_NOT_FOUND = 'user not found';

    private readonly MailCrypt $mailCrypt;

    public function __construct(
        private readonly MailCryptKeyHandler $mailCryptKeyHandler,
        private readonly UserAuthenticationHandler $authHandler,
        private readonly string $mailLocation,
        private readonly int $mailCryptEnv,
        private readonly int $mailUid,
        private readonly int $mailGid,
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
        // Spammers are not excluded from lookup
        if ($user->isDeleted()) {
            return $this->json(['message' => self::MESSAGE_USER_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        if (
            $this->mailCrypt->isAtLeast(MailCrypt::ENABLED_OPTIONAL) &&
            $user->hasMailCrypt() &&
            $user->hasMailCryptPublicKey()
        ) {
            $mailCryptReported = 2;
        } else {
            $mailCryptReported = 0;
        }
        [$username, $domain] = explode('@', $user->getEmail());

        return $this->json([
            'message' => self::MESSAGE_SUCCESS,
            'body' => [
                'user' => $user->getEmail(),
                'home' => $this->mailLocation.DIRECTORY_SEPARATOR.$domain.DIRECTORY_SEPARATOR.$username,
                'mailCrypt' => $mailCryptReported,
                'mailCryptPublicKey' =>  $user->getMailCryptPublicKey() ?? "",
                'gid' => $this->mailGid,
                'uid' => $this->mailUid,
                'quota' => $user->getQuota() ?? "",
            ]
        ], Response::HTTP_OK);
    }

    #[Route('/api/dovecot/{email}', name: 'api_dovecot_user_authenticate', methods: ['POST'], stateless: true)]
    public function authenticate(
        #[MapEntity(mapping: ['email' => 'email'])] User $user,
        #[MapRequestPayload] DovecotPassdbDto $request,
    ): JsonResponse {
        // Spammers are excluded from login
        if ($user->isDeleted() || $user->hasRole(Roles::SPAM)) {
            return $this->json(['message' => self::MESSAGE_USER_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        if (null === $this->authHandler->authenticate($user, $request->getPassword())) {
            return $this->json(['message' => self::MESSAGE_AUTHENTICATION_FAILED], Response::HTTP_UNAUTHORIZED);
        }

        // If mailCrypt is enforced for all users, optionally create mailCrypt keypair for user
        if (
            $this->mailCrypt === MailCrypt::ENABLED_ENFORCE_ALL_USERS &&
            false === $user->getMailCrypt() &&
            null === $user->getMailCryptPublicKey()
        ) {
            try {
                $this->mailCryptKeyHandler->create($user, $request->getPassword(), true);
            } catch (Exception $exception) {
                return $this->json(['error' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // If mailCrypt is enabled and enabled for user, derive mailCryptPrivateKey
        if ($this->mailCrypt->isAtLeast(MailCrypt::ENABLED_OPTIONAL) && $user->hasMailCrypt()) {
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
                'mailCryptPrivateKey' => $privateKey ?? "",
            ]
        ], Response::HTTP_OK);
    }
}
