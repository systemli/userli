<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\PostfixAuthDto;
use App\Enum\AliasCacheKey;
use App\Enum\ApiScope;
use App\Enum\DomainCacheKey;
use App\Enum\Roles;
use App\Enum\UserCacheKey;
use App\Handler\UserAuthenticationHandler;
use App\Repository\AliasRepository;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use App\Security\RequireApiScope;
use App\Service\RfcAliasResolver;
use App\Service\SettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[RequireApiScope(scope: ApiScope::POSTFIX)]
final class PostfixController extends AbstractController
{
    public const string MESSAGE_SUCCESS = 'success';

    public const string MESSAGE_AUTHENTICATION_FAILED = 'authentication failed';

    public const string MESSAGE_USER_DISABLED = 'user disabled due to spam role';

    public const string MESSAGE_USER_PASSWORD_CHANGE_REQUIRED = 'user password change required';

    public function __construct(
        private readonly AliasRepository $aliasRepository,
        private readonly DomainRepository $domainRepository,
        private readonly UserRepository $userRepository,
        private readonly CacheInterface $cache,
        private readonly SettingsService $settingsService,
        private readonly RfcAliasResolver $rfcAliasResolver,
        private readonly UserAuthenticationHandler $authHandler,
    ) {
    }

    #[Route(path: '/api/postfix/alias/{alias}', name: 'api_postfix_get_alias_users', methods: ['GET'], stateless: true)]
    public function getAliasUsers(string $alias): Response
    {
        // RFC addresses are resolved from settings (cached separately), skip alias cache
        if ($this->rfcAliasResolver->isRfcAddress($alias)) {
            return $this->json($this->rfcAliasResolver->resolveDestinations($alias));
        }

        $result = $this->cache->get(AliasCacheKey::POSTFIX_ALIAS->key($alias), function (ItemInterface $item) use ($alias) {
            $item->expiresAfter(AliasCacheKey::TTL);

            return $this->aliasRepository->findDestinationsBySource($alias);
        });

        return $this->json($result);
    }

    #[Route(path: '/api/postfix/domain/{name}', name: 'api_postfix_get_domain', methods: ['GET'], stateless: true)]
    public function getDomain(string $name): Response
    {
        $exists = $this->cache->get(DomainCacheKey::POSTFIX_DOMAIN->key($name), function (ItemInterface $item) use ($name) {
            $item->expiresAfter(DomainCacheKey::TTL);

            return $this->domainRepository->existsByName($name);
        });

        return $this->json($exists);
    }

    #[Route(path: '/api/postfix/mailbox/{email}', name: 'api_postfix_get_mailbox', methods: ['GET'], stateless: true)]
    public function getMailbox(string $email): Response
    {
        $exists = $this->cache->get(UserCacheKey::POSTFIX_MAILBOX->key($email), function (ItemInterface $item) use ($email) {
            $item->expiresAfter(UserCacheKey::TTL);

            return $this->userRepository->existsByEmail($email);
        });

        return $this->json($exists);
    }

    #[Route(path: '/api/postfix/senders/{email}', name: 'api_postfix_get_senders', methods: ['GET'], stateless: true)]
    public function getSenders(string $email): Response
    {
        $senders = $this->cache->get(UserCacheKey::POSTFIX_SENDERS->key($email), function (ItemInterface $item) use ($email) {
            $item->expiresAfter(UserCacheKey::TTL);

            $senders = [];

            if ($this->userRepository->existsByEmail($email)) {
                $senders[] = $email;
            }

            $senders = array_merge($senders, $this->aliasRepository->findDestinationsBySource($email));

            return array_values(array_unique($senders));
        });

        return $this->json($senders);
    }

    #[Route(path: '/api/postfix/smtp_quota/{email}', name: 'api_postfix_get_smtp_quota', methods: ['GET'], stateless: true)]
    public function getSmtpQuota(string $email): Response
    {
        $limits = $this->cache->get(UserCacheKey::POSTFIX_QUOTA->key($email), function (ItemInterface $item) use ($email) {
            $item->expiresAfter(UserCacheKey::TTL);

            return $this->userRepository->findSmtpQuotaLimitsByEmail($email)
                ?? $this->aliasRepository->findSmtpQuotaLimitsBySource($email);
        });

        if ($limits === null) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'per_hour' => $limits['per_hour'] ?? (int) $this->settingsService->get('smtp_quota_limit_per_hour', 0),
            'per_day' => $limits['per_day'] ?? (int) $this->settingsService->get('smtp_quota_limit_per_day', 0),
        ]);
    }

    #[Route(path: '/api/postfix/auth', name: 'api_postfix_authenticate', methods: ['POST'], stateless: true)]
    public function authenticate(#[MapRequestPayload] PostfixAuthDto $request): JsonResponse
    {
        $user = $this->userRepository->findByEmail($request->getEmail());

        if (null === $user || $user->isDeleted()) {
            return $this->json(['message' => self::MESSAGE_AUTHENTICATION_FAILED], Response::HTTP_UNAUTHORIZED);
        }

        if (null === $this->authHandler->authenticate($user, $request->getPassword())) {
            return $this->json(['message' => self::MESSAGE_AUTHENTICATION_FAILED], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->hasRole(Roles::SPAM)) {
            return $this->json(['message' => self::MESSAGE_USER_DISABLED], Response::HTTP_FORBIDDEN);
        }

        if ($user->isPasswordChangeRequired()) {
            return $this->json(['message' => self::MESSAGE_USER_PASSWORD_CHANGE_REQUIRED], Response::HTTP_FORBIDDEN);
        }

        return $this->json(['message' => self::MESSAGE_SUCCESS]);
    }
}
