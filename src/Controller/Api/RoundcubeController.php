<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\RoundcubeUserAliasesDto;
use App\Entity\Alias;
use App\Entity\User;
use App\Enum\ApiScope;
use App\Handler\UserAuthenticationHandler;
use App\Security\RequireApiScope;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[RequireApiScope(scope: ApiScope::ROUNDCUBE)]
final class RoundcubeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly UserAuthenticationHandler $userAuthenticationHandler,
    ) {
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
