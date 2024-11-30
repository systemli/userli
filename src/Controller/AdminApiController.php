<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
    ) {
    }

    #[Route(path: '/api/admin/deleted_users', name: 'admin_deleted_users', methods: ['GET'], stateless: true)]
    public function getDeletedUsers(): Response
    {
        $domain = $this->manager->getRepository(Domain::class)->getDefaultDomain();
        $deletedUsers = $this->manager->getRepository(User::class)->findDeletedUsers($domain);
        $deletedUsernames = array_map(static function ($user) { return $user->getEmail(); }, $deletedUsers);
        return $this->json($deletedUsernames);
    }
}
