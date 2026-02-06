<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UserLastLoginUpdateService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Updates the last login time of the user.
     *
     * The update is obfuscated by only updating the last login time if it is not already set to the start
     * of the current week.
     */
    public function updateLastLogin(User $user): void
    {
        $thisWeek = new DateTimeImmutable('monday this week midnight');

        // If the last login time is already set to this week, do not update it again
        if ($user->getLastLoginTime() && $user->getLastLoginTime()->getTimestamp() === $thisWeek->getTimestamp()) {
            return;
        }

        $user->setLastLoginTime($thisWeek);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
