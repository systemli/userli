<?php

namespace App\Controller;

use App\Dto\KeycloakUserValidateDto;
use App\Entity\Domain;
use App\Entity\User;
use App\Handler\UserAuthenticationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

class KeycloakController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $manager, private readonly UserAuthenticationHandler $handler)
    {}

    #[Route(path: '/api/keycloak', name: 'api_keycloak_index', methods: ['GET'], stateless: true)]
    public function getUsersSearch(
        #[MapQueryParameter] string $domain,
        #[MapQueryParameter] string $search = '',
        #[MapQueryParameter] int $max = 10,
        #[MapQueryParameter] int $first = 0,
    ): Response
    {
        if (null === $domainObject = $this->manager->getRepository(Domain::class)->findByName($domain)) {
            return $this->json([], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $users = $this->manager->getRepository(User::class)->findUsersByString($domainObject, $search, $max, $first)->map(function (User $user) {
            return [
                'id' => explode('@', $user->getEmail())[0],
                'email' => $user->getEmail(),
            ];
        });
        return $this->json($users);
    }

    #[Route(path: '/api/keycloak/count', name: 'api_keycloak_count', methods: ['GET'], stateless: true)]
    public function getUsersCount(#[MapQueryParameter] string $domain): Response
    {
        if (null === $domainObject = $this->manager->getRepository(Domain::class)->findByName($domain)) {
            return $this->json([], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->manager->getRepository(User::class)->countDomainUsers($domainObject));
    }

    #[Route(path: '/api/keycloak/user/{email}', name: 'api_keycloak_user', methods: ['GET'], stateless: true)]
    public function getOneUser(
        #[MapQueryParameter] string $domain,
        string $email,
    ): Response
    {
        if (null === $domainObject = $this->manager->getRepository(Domain::class)->findByName($domain)) {
            return $this->json([], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!str_contains($email, '@')) {
            $email .= '@' . $domainObject->getName();
        }

        if (null === $foundUser = $this->manager->getRepository(User::class)->findByDomainAndEmail($domainObject, $email)) {
            return $this->json([
                'message' => 'user not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => explode('@', $foundUser->getEmail())[0],
            'email' => $foundUser->getEmail(),
        ]);
    }

    #[Route(path: '/api/keycloak/validate/{email}', name: 'api_keycloak_user_validate', methods: ['POST'], stateless: true)]
    public function postUserValidate(#[MapRequestPayload] KeycloakUserValidateDto $requestData, string $email): Response
    {
        if (null === $domainObject = $this->manager->getRepository(Domain::class)->findByName($requestData->domain)) {
            return $this->json([], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (null === $user = $this->manager->getRepository(User::class)->findByDomainAndEmail($domainObject, $email)) {
            return $this->json([
                'message' => 'authentication failed',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($this->handler->authenticate($user, $requestData->password) === null) {
            return $this->json([
                'message' => 'authentication failed',
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'message' => 'success',
        ]);
    }
}
