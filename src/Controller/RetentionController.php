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

/**
 * Retention tracking API for updating last-login timestamps and listing inactive users.
 *
 * Useful for services with long-lived sessions (e.g. Matrix Synapse) that don't
 * re-authenticate regularly against Dovecot. Requires the "retention" API scope.
 */
#[RequireApiScope(scope: ApiScope::RETENTION)]
final class RetentionController extends AbstractController
{
    public const string MESSAGE_TIMESTAMP_IN_FUTURE = 'timestamp in future';

    public function __construct(
        private readonly EntityManagerInterface $manager,
    ) {
    }

    /** Update a user's last-login time. Accepts an optional timestamp (must not be in the future). */
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

    /** List email addresses of users inactive for 2+ years. */
    #[Route(path: '/api/retention/users', name: 'api_retention_get_inactive_users', methods: ['GET'], stateless: true)]
    public function getInactiveUsers(): Response
    {
        $inactiveUsers = $this->manager->getRepository(User::class)->findInactiveUsers(2 * 365); // 2 years
        $inactiveEmails = array_map(static fn ($user) => $user->getEmail(), $inactiveUsers);

        return $this->json($inactiveEmails);
    }
}
