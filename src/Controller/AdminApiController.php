<?php

namespace App\Controller;

use App\Dto\AdminTouchUserDto;
use App\Entity\Domain;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

class AdminApiController extends AbstractController
{
    const MESSAGE_USER_NOT_FOUND = 'user not found';
    const MESSAGE_TIMESTAMP_IN_FUTURE = 'timestamp in future';

    public function __construct(
        private readonly EntityManagerInterface $manager,
    ) {
    }

    #[Route(path: '/api/admin/touch_user', name: 'admin_touch_user', methods: ['PUT'], stateless: true)]
    public function putTouchUser(
        #[MapRequestPayload] AdminTouchUserDto $requestData,
    ): Response
    {
        $email = $requestData->getEmail();
        $user = $this->manager->getRepository(User::class)->findOneByEmail(['email' => $email, 'deleted' => false]);

        // Check if exists
        if (null === $user) {
            return $this->json([
                'message' => self::MESSAGE_USER_NOT_FOUND,
            ], Response::HTTP_NOT_FOUND);
        }

        $now = new \DateTime;
        $time = $requestData->getTimestamp()
            ? new \DateTime('@' . $requestData->getTimestamp())
            : $now;

        // Check that timestamp is not in future
        if ($time > $now) {
            return $this->json([
                'message' => self::MESSAGE_TIMESTAMP_IN_FUTURE,
            ], Response::HTTP_FORBIDDEN);
        }

        if ($time > $user->getLastLoginTime()) {
            $user->setLastLoginTime($time);
            $this->manager->persist($user);
            $this->manager->flush();
        }

        return $this->json([]);
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
