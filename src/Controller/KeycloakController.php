<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\KeycloakUserValidateDto;
use App\Entity\Domain;
use App\Entity\User;
use App\Handler\UserAuthenticationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class KeycloakController extends AbstractController
{
    const MESSAGE_SUCCESS = 'success';

    const MESSAGE_AUTHENTICATION_FAILED = 'authentication failed';

    const MESSAGE_PASSWORD_CHANGE_REQUIRED = 'password change required';

    const MESSAGE_USER_NOT_FOUND = 'user not found';

    const MESSAGE_NOT_SUPPORTED = 'not supported';

    public function __construct(private readonly EntityManagerInterface $manager, private readonly UserAuthenticationHandler $handler, private readonly TotpAuthenticator $totpAuthenticator)
    {
    }

    #[Route(path: '/api/keycloak/{domainUrl}', name: 'api_keycloak_index', methods: ['GET'], stateless: true)]
    public function getUsersSearch(
        #[MapEntity(mapping: ['domainUrl' => 'name'])] Domain $domain,
        #[MapQueryParameter] string                           $search = '',
        #[MapQueryParameter] int                              $max = 10,
        #[MapQueryParameter] int                              $first = 0,
    ): Response
    {
        $users = $this->manager->getRepository(User::class)->findUsersByString($domain, $search, $max, $first)->map(function (User $user) {
            return [
                'id' => explode('@', $user->getEmail())[0],
                'email' => $user->getEmail(),
            ];
        });
        return $this->json($users);
    }

    #[Route(path: '/api/keycloak/{domainUrl}/count', name: 'api_keycloak_count', methods: ['GET'], stateless: true)]
    public function getUsersCount(#[MapEntity(mapping: ['domainUrl' => 'name'])] Domain $domain): Response
    {
        return $this->json($this->manager->getRepository(User::class)->countDomainUsers($domain));
    }

    #[Route(path: '/api/keycloak/{domainUrl}/user/{email}', name: 'api_keycloak_user', methods: ['GET'], stateless: true)]
    public function getOneUser(
        #[MapEntity(mapping: ['domainUrl' => 'name'])] Domain $domain,
        string                                                $email,
    ): Response
    {
        if (!str_contains($email, '@')) {
            $email .= '@' . $domain->getName();
        }

        if (null === $foundUser = $this->manager->getRepository(User::class)->findByDomainAndEmail($domain, $email)) {
            return $this->json([
                'message' => 'user not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => explode('@', $foundUser->getEmail())[0],
            'email' => $foundUser->getEmail(),
        ]);
    }

    #[Route(path: '/api/keycloak/{domainUrl}/validate/{email}', name: 'api_keycloak_user_validate', methods: ['POST'], stateless: true)]
    public function postUserValidate(
        #[MapEntity(mapping: ['domainUrl' => 'name'])] Domain $domain,
        #[MapRequestPayload] KeycloakUserValidateDto          $requestData,
        string                                                $email,
    ): Response
    {
        if (null === $user = $this->manager->getRepository(User::class)->findByDomainAndEmail($domain, $email)) {
            return $this->json([
                'message' => self::MESSAGE_AUTHENTICATION_FAILED,
            ], Response::HTTP_FORBIDDEN);
        }

        return match ($requestData->getCredentialType()) {
            'password' => $this->handlePasswordValidate($user, $requestData),
            'otp' => $this->handleTotpValidate($user, $requestData),
            default => $this->json([
                'message' => self::MESSAGE_NOT_SUPPORTED,
            ], Response::HTTP_BAD_REQUEST),
        };
    }

    #[Route(path: '/api/keycloak/{domainUrl}/configured/{credentialType}/{email}', name: 'api_keycloak_user_configured', methods: ['GET'], stateless: true)]
    public function getIsConfiguredFor(#[MapEntity(mapping: ['domainUrl' => 'name'])] Domain $domain, string $credentialType, string $email): Response
    {
        if (null === $user = $this->manager->getRepository(User::class)->findByDomainAndEmail($domain, $email)) {
            return $this->json([
                'message' => self::MESSAGE_USER_NOT_FOUND,
            ], Response::HTTP_NOT_FOUND);
        }

        if ($user->isPasswordChangeRequired()) {
            return $this->json(['message' => self::MESSAGE_PASSWORD_CHANGE_REQUIRED], Response::HTTP_FORBIDDEN);
        }

        return match ($credentialType) {
            'password' => $this->json(['message' => self::MESSAGE_SUCCESS]),
            'otp' => $this->handleTotpConfigured($user),
            default => $this->json(['message' => self::MESSAGE_NOT_SUPPORTED], Response::HTTP_NOT_FOUND),
        };
    }

    private function handlePasswordValidate(User $user, KeycloakUserValidateDto $requestData): Response
    {
        if ($this->handler->authenticate($user, $requestData->getPassword()) === null) {
            return $this->json([
                'message' => self::MESSAGE_AUTHENTICATION_FAILED,
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'message' => self::MESSAGE_SUCCESS,
        ]);
    }

    private function handleTotpValidate(User $user, KeycloakUserValidateDto $requestData): Response
    {
        if (!$user->isTotpAuthenticationEnabled()) {
            return $this->json([
                'message' => self::MESSAGE_NOT_SUPPORTED,
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$this->totpAuthenticator->checkCode($user, $requestData->getPassword())) {
            return $this->json([
                'message' => self::MESSAGE_AUTHENTICATION_FAILED,
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'message' => self::MESSAGE_SUCCESS,
        ]);
    }

    private function handleTotpConfigured(User $user): Response
    {
        return $user->isTotpAuthenticationEnabled() ? $this->json(['message' => self::MESSAGE_SUCCESS]) : $this->json(['message' => self::MESSAGE_USER_NOT_FOUND], Response::HTTP_NOT_FOUND);
    }
}
