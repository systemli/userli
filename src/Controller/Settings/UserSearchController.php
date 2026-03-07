<?php

declare(strict_types=1);

namespace App\Controller\Settings;

use App\Entity\User;
use App\Enum\Roles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class UserSearchController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/settings/users/search', name: 'settings_user_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->query->getString('q', ''));

        if (mb_strlen($query) < 2) {
            return $this->json([]);
        }

        $users = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.email LIKE :query')
            ->andWhere('u.deleted = :deleted')
            ->setParameter('query', '%'.$query.'%')
            ->setParameter('deleted', false)
            ->setMaxResults(20)
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult();

        $results = array_map(static function (User $user): array {
            $roles = [];
            if ($user->hasRole(Roles::SUSPICIOUS)) {
                $roles[] = Roles::SUSPICIOUS;
            }

            if ($user->hasRole(Roles::SPAM)) {
                $roles[] = Roles::SPAM;
            }

            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $roles,
            ];
        }, $users);

        return $this->json($results);
    }
}
