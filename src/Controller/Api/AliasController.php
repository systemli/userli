<?php

namespace App\Controller\Api;

use App\Dto\AliasDto;
use App\Dto\PasswordDto;
use App\Entity\Alias;
use App\Entity\User;
use App\Handler\AliasHandler;
use App\Handler\DeleteHandler;
use App\Repository\AliasRepository;
use App\Exception\ValidationException;
use App\Validator\Constraints\AliasDelete;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AliasController extends AbstractController
{
    private readonly AliasRepository $aliasRepository;

    public function __construct(
        private readonly AliasHandler $aliasHandler,
        private readonly DeleteHandler $deleteHandler,
        private readonly EntityManagerInterface $manager,
        private ValidatorInterface $validator,
    ) {
        $this->aliasRepository = $manager->getRepository(Alias::class);
        $this->validator = $validator;
    }

    #[Route('/api/user/aliases', name: 'get_user_aliases', methods: ['GET'], stateless: true)]
    public function getAliases(
        #[CurrentUser] User $user
    ): JsonResponse {
        $customAliasData = [];
        if ($customAliases = $this->aliasRepository->findByUser($user, false, false)) {
            $customAliasData = array_map(function (Alias $alias) {
                return [
                    'id' => $alias->getId(),
                    'source' => $alias->getSource()
                ];
            }, $customAliases);
        }
        $randomAliasData = [];
        if ($randomAliases = $this->aliasRepository->findByUser($user, true, false)) {
            $randomAliasData = array_map(function (Alias $alias) {
                return [
                    'id' => $alias->getId(),
                    'source' => $alias->getSource()
                ];
            }, $randomAliases);
        }
        return $this->json(
            [
                'status' => 'success',
                'customAliases' => $customAliasData,
                'randomAliases' => $randomAliasData,
            ],
            200
        );
    }

    /**
     * Creates random alias if request->localpart === null; custom alias if string
     * Request body must not be empty
     */
    #[Route('/api/user/aliases', name: 'post_user_alias', methods: ['POST'], stateless: true)]
    public function createRandomAlias(
        #[CurrentUser] User $user,
        #[MapRequestPayload] AliasDto $request,
    ): JsonResponse {
        try {
            $alias = $this->aliasHandler->create($user, $request->localpart);
        } catch (ValidationException $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'email address is unavailable'
            ], 400);
        }

        return $this->json([
            'status' => 'success',
            'alias' => [
                'id' => $alias->getId(),
                'source' => $alias->getSource()
            ]
        ], 200);
    }

    /**
     * Delegates password validation to Password
     */
    #[Route('/api/user/aliases/{id}', name: 'delete_user_alias', methods: ['DELETE'], stateless: true)]
    public function getAlias(
        #[MapRequestPayload] PasswordDto $request,
        Alias $alias
    ): JsonResponse {
        $violations = $this->validator->validate($alias, new AliasDelete());
        if (count($violations) > 0) {
            $message = "";
            foreach ($violations as $violation) {
                $message .= $violation->getMessage() . '\n';
            }
            return $this->json(['status' => 'error', 'message' => $message], 403);
        }

        $this->deleteHandler->deleteAlias($alias);
        return $this->json(['status' => 'success'], 200);
    }
}
