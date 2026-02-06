<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\ApiScope;
use App\Repository\AliasRepository;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use App\Security\RequireApiScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[RequireApiScope(scope: ApiScope::POSTFIX)]
final class PostfixController extends AbstractController
{
    private const int CACHE_TTL = 60;

    public function __construct(
        private readonly AliasRepository $aliasRepository,
        private readonly DomainRepository $domainRepository,
        private readonly UserRepository $userRepository,
        private readonly CacheInterface $cache,
    ) {
    }

    #[Route(path: '/api/postfix/alias/{alias}', name: 'api_postfix_get_alias_users', methods: ['GET'], stateless: true)]
    public function getAliasUsers(string $alias): Response
    {
        $result = $this->cache->get('postfix_alias_'.sha1($alias), function (ItemInterface $item) use ($alias) {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->aliasRepository->findDestinationsBySource($alias);
        });

        return $this->json($result);
    }

    #[Route(path: '/api/postfix/domain/{name}', name: 'api_postfix_get_domain', methods: ['GET'], stateless: true)]
    public function getDomain(string $name): Response
    {
        $exists = $this->cache->get('postfix_domain_'.sha1($name), function (ItemInterface $item) use ($name) {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->domainRepository->existsByName($name);
        });

        return $this->json($exists);
    }

    #[Route(path: '/api/postfix/mailbox/{email}', name: 'api_postfix_get_mailbox', methods: ['GET'], stateless: true)]
    public function getMailbox(string $email): Response
    {
        $exists = $this->cache->get('postfix_mailbox_'.sha1($email), function (ItemInterface $item) use ($email) {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->userRepository->existsByEmail($email);
        });

        return $this->json($exists);
    }

    #[Route(path: '/api/postfix/senders/{email}', name: 'api_postfix_get_senders', methods: ['GET'], stateless: true)]
    public function getSenders(string $email): Response
    {
        $senders = $this->cache->get('postfix_senders_'.sha1($email), function (ItemInterface $item) use ($email) {
            $item->expiresAfter(self::CACHE_TTL);

            $senders = [];

            if ($this->userRepository->existsByEmail($email)) {
                $senders[] = $email;
            }

            $senders = array_merge($senders, $this->aliasRepository->findDestinationsBySource($email));

            return array_values(array_unique($senders));
        });

        return $this->json($senders);
    }
}
