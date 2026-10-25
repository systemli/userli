<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\RoundcubeLoginDto;
use App\Dto\RoundcubeUserAliasesDto;
use App\Entity\Alias;
use App\Entity\User;
use App\Enum\ApiScope;
use App\Enum\Roles;
use App\Handler\UserAuthenticationHandler;
use App\Security\RequireApiScope;
use App\Service\AppPasswordManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

use const DATE_ATOM;

#[RequireApiScope(scope: ApiScope::ROUNDCUBE)]
final class RoundcubeController extends AbstractController
{
    private const string MESSAGE_AUTHENTICATION_FAILED = 'authentication failed';

    private const string MESSAGE_TWOFACTOR_REQUIRED = 'twofactor required';

    private const string MESSAGE_TOO_MANY_ATTEMPTS = 'too many attempts';

    private const string MESSAGE_USER_DISABLED = 'user disabled due to spam role';

    private const string MESSAGE_USER_PASSWORD_CHANGE_REQUIRED = 'user password change required';

    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly UserAuthenticationHandler $userAuthenticationHandler,
        private readonly AppPasswordManager $appPasswordManager,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
        private readonly BackupCodeManagerInterface $backupCodeManager,
        private readonly RateLimiterFactoryInterface $roundcubeLoginLimiter,
    ) {
    }

    /**
     * Checks the main password and 2FA code and returns a webmail credential, which Roundcube uses for IMAP and SMTP.
     *
     * @throws Exception
     */
    #[Route(path: '/api/roundcube/login', name: 'api_roundcube_post_login', methods: ['POST'], stateless: true)]
    public function postLogin(#[MapRequestPayload] RoundcubeLoginDto $data): JsonResponse
    {
        // Unlike IMAP logins, failed attempts here get no Dovecot auth penalty
        $limiter = $this->roundcubeLoginLimiter->create(mb_strtolower($data->getEmail()));
        if (!$limiter->consume()->isAccepted()) {
            return $this->json(['message' => self::MESSAGE_TOO_MANY_ATTEMPTS], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $user = $this->manager->getRepository(User::class)->findByEmail($data->getEmail());
        if (!$user || $user->isDeleted()) {
            return $this->json(['message' => self::MESSAGE_AUTHENTICATION_FAILED], Response::HTTP_UNAUTHORIZED);
        }

        // Same checks as the Dovecot and Postfix APIs, before authenticating, so rejected logins don't count as last login
        if ($user->hasRole(Roles::SPAM)) {
            return $this->json(['message' => self::MESSAGE_USER_DISABLED], Response::HTTP_FORBIDDEN);
        }

        if ($user->isPasswordChangeRequired()) {
            return $this->json(['message' => self::MESSAGE_USER_PASSWORD_CHANGE_REQUIRED], Response::HTTP_FORBIDDEN);
        }

        if (!$this->userAuthenticationHandler->verifyPassword($user, $data->getPassword())) {
            return $this->json(['message' => self::MESSAGE_AUTHENTICATION_FAILED], Response::HTTP_UNAUTHORIZED);
        }

        $backupCode = null;
        if ($user->isTotpAuthenticationEnabled()) {
            $code = $data->getCode();
            if ('' === $code) {
                return $this->json(['message' => self::MESSAGE_TWOFACTOR_REQUIRED], Response::HTTP_UNAUTHORIZED);
            }

            if (!$this->totpAuthenticator->checkCode($user, $code)) {
                if (!$this->backupCodeManager->isBackupCode($user, $code)) {
                    return $this->json(['message' => self::MESSAGE_AUTHENTICATION_FAILED], Response::HTTP_UNAUTHORIZED);
                }

                $backupCode = $code;
            }
        }

        $this->userAuthenticationHandler->dispatchLoginEvent($user, $data->getPassword());
        [$password, $expiresAt] = $this->appPasswordManager->createWebmailCredential($user, $data->getPassword());

        // Only now, so a failure above does not use up the backup code
        if (null !== $backupCode) {
            $this->backupCodeManager->invalidateBackupCode($user, $backupCode);
        }

        $limiter->reset();

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
