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

#[RequireApiScope(scope: ApiScope::POSTFIX)]
final class PostfixController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    #[Route(path: '/api/postfix/alias/{alias}', name: 'api_postfix_get_alias_users', methods: ['GET'], stateless: true)]
    public function getAliasUsers(string $alias): Response
    {
        $users = $this->manager->getRepository(Alias::class)->findBy(['deleted' => false, 'source' => $alias]);

        return $this->json(array_map(static function (Alias $alias) {
            return $alias->getDestination();
        }, $users));
    }

    #[Route(path: '/api/postfix/domain/{name}', name: 'api_postfix_get_domain', methods: ['GET'], stateless: true)]
    public function getDomain(string $name): Response
    {
        $domain = $this->manager->getRepository(Domain::class)->findOneBy(['name' => $name]);
        $exists = $domain !== null;

        return $this->json($exists);
    }

    #[Route(path: '/api/postfix/mailbox/{email}', name: 'api_postfix_get_mailbox', methods: ['GET'], stateless: true)]
    public function getMailbox(string $email): Response
    {
        $user = $this->manager->getRepository(User::class)->findOneBy(['email' => $email, 'deleted' => false]);
        $exists = $user !== null;

        return $this->json($exists);
    }

    #[Route(path: '/api/postfix/senders/{email}', name: 'api_postfix_get_senders', methods: ['GET'], stateless: true)]
    public function getSenders(string $email): Response
    {
        $users = $this->manager->getRepository(User::class)->findBy(['deleted' => false, 'email' => $email]);
        $aliases = $this->manager->getRepository(Alias::class)->findBy(['deleted' => false, 'source' => $email]);

        // Extract email addresses from users
        $senders = array_map(static function (User $user) {
            return $user->getEmail();
        }, $users);

        // Extract email addresses from alias destinations
        $senders = array_merge($senders, array_map(static function (Alias $alias) {
            return $alias->getDestination();
        }, $aliases));

        // Remove duplicates
        $senders = array_unique($senders);

        return $this->json($senders);
    }
}
