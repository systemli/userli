<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\RetentionTouchUserDto;
use App\Entity\User;
use App\Enum\ApiScope;
use App\Security\RequireApiScope;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[RequireApiScope(scope: ApiScope::RETENTION)]
final class RetentionController extends AbstractController
{
    public const MESSAGE_TIMESTAMP_IN_FUTURE = 'timestamp in future';

    public function __construct(
        private readonly EntityManagerInterface $manager,
    ) {
    }

    #[Route(path: '/api/retention/{email}/touch', name: 'api_retention_put_touch_user', methods: ['PUT'], stateless: true)]
    public function putTouchUser(
        #[MapEntity(mapping: ['email' => 'email'])] User $user,
        #[MapRequestPayload] RetentionTouchUserDto $requestData,
    ): Response {
        $now = new DateTimeImmutable();
        $time = $requestData->getTimestamp()
            ? new DateTimeImmutable('@'.$requestData->getTimestamp())
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
     * List inactive users.
     */
    #[Route(path: '/api/retention/users', name: 'api_retention_get_inactive_users', methods: ['GET'], stateless: true)]
    public function getInactiveUsers(): Response
    {
        $inactiveUsers = $this->manager->getRepository(User::class)->findInactiveUsers(2 * 365); // 2 years
        $inactiveEmails = array_map(static function ($user) { return $user->getEmail(); }, $inactiveUsers);

        return $this->json($inactiveEmails);
    }
}
