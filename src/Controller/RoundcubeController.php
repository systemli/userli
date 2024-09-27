<?php

namespace App\Controller;

use App\Entity\Alias;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RoundcubeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
    ) {
    }

    #[Route(path: '/api/roundcube', name: 'api_roundcube', methods: ['GET'], stateless: true)]
    public function getUserAliases(): Response
    {
        $user = $this->getUser();

        $aliases = $this->manager->getRepository(Alias::class)->findByUser($user);
        $aliasSources = array_map(static function ($alias) { return $alias->getSource(); }, $aliases);
        return $this->json($aliasSources);
    }
}
