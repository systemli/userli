<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Enum\ApiScope;
use App\Repository\AliasRepository;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use App\Security\RequireApiScope;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[RequireApiScope(scope: ApiScope::POSTFIX)]
final class PostfixController extends AbstractController
{
    private readonly AliasRepository $aliasRepository;
    private readonly DomainRepository $domainRepository;
    private readonly UserRepository $userRepository;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->aliasRepository = $manager->getRepository(Alias::class);
        $this->domainRepository = $manager->getRepository(Domain::class);
        $this->userRepository = $manager->getRepository(User::class);
    }

    #[Route(path: '/api/postfix/alias/{alias}', name: 'api_postfix_get_alias_users', methods: ['GET'], stateless: true)]
    public function getAliasUsers(string $alias): Response
    {
        return $this->json($this->aliasRepository->findDestinationsBySource($alias));
    }

    #[Route(path: '/api/postfix/domain/{name}', name: 'api_postfix_get_domain', methods: ['GET'], stateless: true)]
    public function getDomain(string $name): Response
    {
        return $this->json($this->domainRepository->existsByName($name));
    }

    #[Route(path: '/api/postfix/mailbox/{email}', name: 'api_postfix_get_mailbox', methods: ['GET'], stateless: true)]
    public function getMailbox(string $email): Response
    {
        return $this->json($this->userRepository->existsByEmail($email));
    }

    #[Route(path: '/api/postfix/senders/{email}', name: 'api_postfix_get_senders', methods: ['GET'], stateless: true)]
    public function getSenders(string $email): Response
    {
        $senders = [];

        if ($this->userRepository->existsByEmail($email)) {
            $senders[] = $email;
        }

        $senders = array_merge($senders, $this->aliasRepository->findDestinationsBySource($email));

        return $this->json(array_values(array_unique($senders)));
    }
}
