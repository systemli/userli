<?php

namespace App\Controller;

use App\Dto\KeycloakUserValidateDto;
use App\Entity\Domain;
use App\Entity\User;
use App\Handler\UserAuthenticationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

class KeycloakController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $manager, private readonly UserAuthenticationHandler $handler)
    {}

    #[Route(path: '/api/keycloak/{domainUrl}', name: 'api_keycloak_index', methods: ['GET'], stateless: true)]
    public function getUsersSearch(
        #[MapEntity(mapping: ['domainUrl' => 'name'])] Domain $domain,
        #[MapQueryParameter] string $search = '',
        #[MapQueryParameter] int $max = 10,
        #[MapQueryParameter] int $first = 0,
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
        string $email,
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
        #[MapRequestPayload] KeycloakUserValidateDto $requestData,
        string $email,
    ): Response
    {
        if (null === $user = $this->manager->getRepository(User::class)->findByDomainAndEmail($domain, $email)) {
            return $this->json([
                'message' => 'authentication failed',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($this->handler->authenticate($user, $requestData->getPassword()) === null) {
            return $this->json([
                'message' => 'authentication failed',
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'message' => 'success',
        ]);
    }
}
