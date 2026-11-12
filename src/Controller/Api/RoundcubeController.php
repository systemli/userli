<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\RoundcubeLoginDto;
use App\Dto\RoundcubeUserAliasesDto;
use App\Entity\Alias;
use App\Entity\User;
use App\Enum\ApiScope;
use App\Exception\DecryptionFailedException;
use App\Handler\UserAuthenticationHandler;
use App\Security\RequireApiScope;
use App\Service\AppPasswordManager;
use App\Service\WebauthnService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

use const DATE_ATOM;

#[RequireApiScope(scope: ApiScope::ROUNDCUBE)]
final class RoundcubeController extends AbstractController
{
    private const string MESSAGE_AUTHENTICATION_FAILED = 'authentication failed';

    private const string MESSAGE_TWOFACTOR_REQUIRED = 'twofactor required';

    private const string MESSAGE_USER_PASSWORD_CHANGE_REQUIRED = 'user password change required';

    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly UserAuthenticationHandler $userAuthenticationHandler,
        private readonly AppPasswordManager $appPasswordManager,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
        private readonly BackupCodeManagerInterface $backupCodeManager,
        private readonly WebauthnService $webauthnService,
    ) {
    }

    /**
     * Checks the main password and the second factor (TOTP code, backup code or security key) and returns
     * a webmail credential, which Roundcube uses for IMAP and SMTP.
     *
     * @throws Exception
     */
    #[Route(path: '/api/roundcube/login', name: 'api_roundcube_post_login', methods: ['POST'], stateless: true)]
    public function postLogin(#[MapRequestPayload] RoundcubeLoginDto $data): JsonResponse
    {
        $user = $this->manager->getRepository(User::class)->findByEmail($data->getEmail());
        if (!$user || $user->isDeleted()) {
            return $this->json(['message' => self::MESSAGE_AUTHENTICATION_FAILED], Response::HTTP_UNAUTHORIZED);
        }

        // Password first, so the account state isn't revealed without it. verifyPassword() dispatches no login event.
        if (!$this->userAuthenticationHandler->verifyPassword($user, $data->getPassword())) {
            return $this->json(['message' => self::MESSAGE_AUTHENTICATION_FAILED], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->isPasswordChangeRequired()) {
            return $this->json(['message' => self::MESSAGE_USER_PASSWORD_CHANGE_REQUIRED], Response::HTTP_FORBIDDEN);
        }

        if ($user->isTwofactorEnabled() && !$this->checkSecondFactor($user, $data)) {
            $missing = '' === $data->getCode() && null === $data->getWebauthn();
            $body = ['message' => $missing ? self::MESSAGE_TWOFACTOR_REQUIRED : self::MESSAGE_AUTHENTICATION_FAILED];
            // Only after the password, so the keys of a user aren't revealed without it. A new challenge for each attempt.
            if ($user->isWebauthnAuthenticationEnabled()) {
                $body['webauthn'] = $this->webauthnService->createExternalAuthenticationOptions($user);
            }

            return $this->json($body, Response::HTTP_UNAUTHORIZED);
        }

        return $this->issueWebmailCredential($user, $data);
    }

    private function checkSecondFactor(User $user, RoundcubeLoginDto $data): bool
    {
        $assertion = $data->getWebauthn();
        if (null !== $assertion) {
            return $user->isWebauthnAuthenticationEnabled() && $this->webauthnService->verifyExternalAssertion(
                $user,
                $assertion->getChallenge(),
                $assertion->getCredentialId(),
                $assertion->getClientDataJSON(),
                $assertion->getAuthenticatorData(),
                $assertion->getSignature(),
                $assertion->getUserHandle(),
            );
        }

        $code = $data->getCode();

        return '' !== $code && (($user->isTotpAuthenticationEnabled() && $this->totpAuthenticator->checkCode($user, $code)) || $this->backupCodeManager->isBackupCode($user, $code));
    }

    /**
     * @throws Exception
     */
    private function issueWebmailCredential(User $user, RoundcubeLoginDto $data): JsonResponse
    {
        // Before the credential: LoginListener may create the MailCrypt key it copies
        $this->userAuthenticationHandler->dispatchLoginEvent($user, $data->getPassword());

        try {
            [$password, $expiresAt] = $this->appPasswordManager->createWebmailCredential($user, $data->getPassword());
        } catch (DecryptionFailedException $decryptionFailedException) {
            // Same response as the Dovecot API for a MailCrypt key that doesn't decrypt
            return $this->json(['error' => $decryptionFailedException->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Only now, so a failure above does not use up a backup code. A no-op for TOTP codes and security keys.
        if ($user->isTwofactorEnabled() && '' !== $data->getCode()) {
            $this->backupCodeManager->invalidateBackupCode($user, $data->getCode());
        }

        return $this->json(['password' => $password, 'expiresAt' => $expiresAt->format(DATE_ATOM)]);
    }

    #[Route(path: '/api/roundcube/logout', name: 'api_roundcube_post_logout', methods: ['POST'], stateless: true)]
    public function postLogout(#[MapRequestPayload] RoundcubeUserAliasesDto $data): Response
    {
        $user = $this->manager->getRepository(User::class)->findByEmail($data->getEmail());
        $appPassword = $user ? $this->appPasswordManager->find($user, $data->getPassword()) : null;

        // Only webmail credentials, so the endpoint can't revoke the user's app passwords
        if (null !== $appPassword?->getExpiresAt()) {
            $this->appPasswordManager->delete($appPassword);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/api/roundcube/aliases', name: 'api_roundcube_post_aliases', methods: ['POST'], stateless: true)]
    public function postUserAliases(
        #[MapRequestPayload] RoundcubeUserAliasesDto $data,
    ): Response {
        $user = $this->manager->getRepository(User::class)->findByEmail($data->getEmail());
        if (!$user || null === $this->userAuthenticationHandler->authenticate($user, $data->getPassword())) {
            throw new AuthenticationException('Bad credentials', 401);
        }

        // Uses findByUserAcrossDomains to include cross-domain aliases. The domain
        // filter is typically not active for API-authenticated requests (see the
        // method's doc comment), but this ensures correct behavior regardless.
        $aliases = $this->manager->getRepository(Alias::class)->findByUserAcrossDomains($user);
        $aliasSources = array_map(static fn ($alias) => $alias->getSource(), $aliases);

        return $this->json($aliasSources);
    }
}
