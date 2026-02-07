<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Enum\ApiScope;
use App\Security\RequireApiScope;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly EntityManagerInterface $manager,
        private readonly CacheInterface $cache,
    ) {
    }

    #[Route(path: '/api/postfix/alias/{alias}', name: 'api_postfix_get_alias_users', methods: ['GET'], stateless: true)]
    public function getAliasUsers(string $alias): Response
    {
        $result = $this->cache->get('postfix_alias_'.sha1($alias), function (ItemInterface $item) use ($alias) {
            $item->expiresAfter(self::CACHE_TTL);

            $aliases = $this->manager->getRepository(Alias::class)->findBy(['deleted' => false, 'source' => $alias]);

            return array_map(static function (Alias $alias) {
                return $alias->getDestination();
            }, $aliases);
        });

        return $this->json($result);
    }

    #[Route(path: '/api/postfix/domain/{name}', name: 'api_postfix_get_domain', methods: ['GET'], stateless: true)]
    public function getDomain(string $name): Response
    {
        $exists = $this->cache->get('postfix_domain_'.sha1($name), function (ItemInterface $item) use ($name) {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->manager->getRepository(Domain::class)->findOneBy(['name' => $name]) !== null;
        });

        return $this->json($exists);
    }

    #[Route(path: '/api/postfix/mailbox/{email}', name: 'api_postfix_get_mailbox', methods: ['GET'], stateless: true)]
    public function getMailbox(string $email): Response
    {
        $exists = $this->cache->get('postfix_mailbox_'.sha1($email), function (ItemInterface $item) use ($email) {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->manager->getRepository(User::class)->findOneBy(['email' => $email, 'deleted' => false]) !== null;
        });

        return $this->json($exists);
    }

    #[Route(path: '/api/postfix/senders/{email}', name: 'api_postfix_get_senders', methods: ['GET'], stateless: true)]
    public function getSenders(string $email): Response
    {
        $senders = $this->cache->get('postfix_senders_'.sha1($email), function (ItemInterface $item) use ($email) {
            $item->expiresAfter(self::CACHE_TTL);

            $users = $this->manager->getRepository(User::class)->findBy(['deleted' => false, 'email' => $email]);
            $aliases = $this->manager->getRepository(Alias::class)->findBy(['deleted' => false, 'source' => $email]);

            $senders = array_map(static function (User $user) {
                return $user->getEmail();
            }, $users);

            $senders = array_merge($senders, array_map(static function (Alias $alias) {
                return $alias->getDestination();
            }, $aliases));

            return array_values(array_unique($senders));
        });

        return $this->json($senders);
    }
}
