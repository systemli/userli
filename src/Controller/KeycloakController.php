<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\User;
use App\Handler\UserAuthenticationHandler;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class KeycloakController extends AbstractController
{
    private readonly DomainRepository $domainRepository;
    private readonly UserRepository $userRepository;

    public function __construct(private readonly EntityManagerInterface $manager, private readonly UserAuthenticationHandler $handler) {
        $this->domainRepository = $this->manager->getRepository(Domain::class);
        $this->userRepository = $this->manager->getRepository(User::class);
    }

    #[Route(path: '/api/keycloak', name: 'api_keycloak_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if (null === $domain = $this->domainRepository->findByName($request->query->get('domain') ?? '')) {
            return $this->json([
                'message' => 'domain not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if (null === $search = $request->query->get('search')) {
            $search = '';
        }

        if (null === $max = $request->query->get('max')) {
            $max = 10;
        }

        if (null === $first = $request->query->get('first')) {
            $first = 0;
        }

        $users = $this->userRepository->findUsersByString($domain, $search, $max, $first)->map(function (User $user) {
            return [
                'id' => explode('@', $user->getEmail())[0],
                'email' => $user->getEmail(),
            ];
        });
        return $this->json($users);
    }

    #[Route(path: '/api/keycloak/count', name: 'api_keycloak_count', methods: ['GET'])]
    public function count(Request $request): Response
    {
        if (null === $domain = $this->domainRepository->findByName($request->query->get('domain') ?? '')) {
            return $this->json([
                'message' => 'domain not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->userRepository->countDomainUsers($domain));
    }

    #[Route(path: '/api/keycloak/user/{email}', name: 'api_keycloak_user', methods: ['GET'])]
    public function get(Request $request, string $email): Response
    {
        if (null === $domain = $this->domainRepository->findByName($request->query->get('domain') ?? '')) {
            return $this->json([
                'message' => 'domain not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if (!str_contains($email, '@')) {
            $email .= '@' . $domain->getName();
        }

        if (null === $foundUser = $this->userRepository->findByDomainAndEmail($domain, $email)) {
            return $this->json([
                'message' => 'user not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => explode('@', $foundUser->getEmail())[0],
            'email' => $foundUser->getEmail(),
        ]);
    }

    #[Route(path: '/api/keycloak/validate/{email}', name: 'api_keycloak_user_validate', methods: ['POST'])]
    public function validate(Request $request, string $email): Response
    {
        if (null === $domain = $this->domainRepository->findByName($request->request->get('domain') ?? '')) {
            return $this->json([
                'message' => 'domain not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if (null === $password = $request->request->get('password')) {
            return $this->json([
                'message' => 'missing password',
            ], Response::HTTP_FORBIDDEN);
        }

        if (null === $user = $this->userRepository->findByDomainAndEmail($domain, $email)) {
            return $this->json([
                'message' => 'authentication failed',
            ], Response::HTTP_FORBIDDEN);
        }

        if (null === $authUser = $this->handler->authenticate($user, $password)) {
            return $this->json([
                'message' => 'authentication failed',
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'message' => 'success',
        ]);
    }
}
