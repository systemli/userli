<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\RoundcubeUserAliasesDto;
use App\Enum\ApiScope;
use App\Handler\UserAuthenticationHandler;
use App\Repository\AliasRepository;
use App\Repository\UserRepository;
use App\Security\RequireApiScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[RequireApiScope(scope: ApiScope::ROUNDCUBE)]
final class RoundcubeController extends AbstractController
{
    public function __construct(
        private readonly UserAuthenticationHandler $userAuthenticationHandler,
        private readonly AliasRepository $aliasRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route(path: '/api/roundcube/aliases', name: 'api_roundcube_post_aliases', methods: ['POST'], stateless: true)]
    public function postUserAliases(
        #[MapRequestPayload] RoundcubeUserAliasesDto $data,
    ): Response {
        $user = $this->userRepository->findByEmail($data->getEmail());
        if (!$user || null === $this->userAuthenticationHandler->authenticate($user, $data->getPassword())) {
            throw new AuthenticationException('Bad credentials', 401);
        }

        $aliases = $this->aliasRepository->findByUser($user, random: false);
        $aliasSources = array_map(static fn ($alias) => $alias->getSource(), $aliases);

        return $this->json($aliasSources);
    }
}
