<?php

namespace App\Controller;

use App\Dto\RetentionTouchUserDto;
use App\Entity\Domain;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class RetentionController extends AbstractController
{
    const MESSAGE_TIMESTAMP_IN_FUTURE = 'timestamp in future';

    public function __construct(
        private readonly EntityManagerInterface $manager,
    ) {
    }

    #[Route(path: '/api/retention/{email}/touch', name: 'retention_touch', methods: ['PUT'], stateless: true)]
    public function putTouchUser(
        #[MapEntity(mapping: ['email' => 'email'])] User $user,
        #[MapRequestPayload] RetentionTouchUserDto $requestData,
    ): Response
    {
        $now = new \DateTime;
        $time = $requestData->getTimestamp()
            ? new \DateTime('@' . $requestData->getTimestamp())
            : $now;

        // Check that timestamp is not in future
        if ($time > $now) {
            return $this->json([
                'message' => self::MESSAGE_TIMESTAMP_IN_FUTURE,
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($time > $user->getLastLoginTime()) {
            $user->setLastLoginTime($time);
            $this->manager->persist($user);
            $this->manager->flush();
        }

        return $this->json([]);
    }

    /**
     * List deleted users
     */
    #[Route(path: '/api/retention/{domainUrl}/users', name: 'retention_users', methods: ['GET'], stateless: true)]
    public function getDeletedUsers(
        #[MapEntity(mapping: ['domainUrl' => 'name'])] Domain $domain,
    ): Response
    {
        $deletedUsers = $this->manager->getRepository(User::class)->findDeletedUsers($domain);
        $deletedUsernames = array_map(static function ($user) { return $user->getEmail(); }, $deletedUsers);
        return $this->json($deletedUsernames);
    }
}
