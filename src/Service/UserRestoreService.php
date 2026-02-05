<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Event\UserEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final readonly class UserRestoreService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private EventDispatcherInterface $eventDispatcher,
        private UserResetService $userResetService,
    ) {
    }

    /**
     * Restore a deleted user with a new password.
     *
     * @return string|null The new recovery token if MailCrypt is enabled, null otherwise
     */
    public function restoreUser(User $user, string $password): ?string
    {
        $recoveryToken = $this->userResetService->resetUser($user, $password);

        $user->setDeleted(false);

        $this->manager->flush();

        $this->eventDispatcher->dispatch(new UserEvent($user), UserEvent::USER_CREATED);

        return $recoveryToken;
    }
}
