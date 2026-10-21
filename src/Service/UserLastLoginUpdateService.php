<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AppPassword;
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
     * Updates the last login time of the user and, if given, of the app password used.
     *
     * The update is obfuscated by only updating the last login time if it is not already set to the start
     * of the current week.
     */
    public function updateLastLogin(User $user, ?AppPassword $appPassword = null): void
    {
        $thisWeek = new DateTimeImmutable('monday this week midnight');
        $changed = false;

        foreach ([$user, $appPassword] as $entity) {
            // If the last login time is already set to this week, do not update it again
            if (null === $entity || $entity->getLastLoginTime()?->getTimestamp() === $thisWeek->getTimestamp()) {
                continue;
            }

            $entity->setLastLoginTime($thisWeek);
            $this->entityManager->persist($entity);
            $changed = true;
        }

        if ($changed) {
            $this->entityManager->flush();
        }
    }
}
