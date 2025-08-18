<?php

namespace App\Service;

use DateTime;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;

readonly class UserLastLoginUpdateService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface        $logger,
    )
    {

    }

    /**
     * Updates the last login time of the user.
     *
     * The update is obfuscated by a 48-hour delay and stores not the exact time of the last login, to prevent
     * surveillance of user activity.
     *
     * @param User $user
     * @return void
     */
    public function updateLastLogin(User $user): void
    {
        // Skip the update if the user has logged in within the last 48 hours
        $lastLoginTime = $user->getLastLoginTime();
        if ($lastLoginTime && $lastLoginTime > (new DateTime())->modify('-48 hours')) {
            return;
        }

        try {
            // Update the last login time to the current time with obfuscation
            $now = new DateTime();
            $obfuscatedTime = $now->modify('-' . random_int(0, 12) . ' hours');
            $user->setLastLoginTime($obfuscatedTime);

            $this->entityManager->persist($user);
        } catch (RandomException $randomException) {
            $this->logger->error(
                'Failed to update last login time',
                [
                    'email' => $user->getEmail(),
                    'error' => $randomException->getMessage()
                ]
            );
        }
    }
}
