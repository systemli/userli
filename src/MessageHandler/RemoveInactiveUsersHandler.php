<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\User;
use App\Enum\Roles;
use App\Message\DeleteUser;
use App\Message\RemoveInactiveUsers;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class RemoveInactiveUsersHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RemoveInactiveUsers $message): void
    {
        $inactiveDays = 2 * 365 + 7; // 2 years and 7 days
        $users = $this->entityManager->getRepository(User::class)->findInactiveUsers($inactiveDays);
        $dispatched = 0;

        $this->logger->info('Found inactive users', ['count' => count($users)]);

        foreach ($users as $user) {
            if ($user->hasRole(Roles::ADMIN) || $user->hasRole(Roles::DOMAIN_ADMIN) || $user->hasRole(Roles::PERMANENT)) {
                continue;
            }

            $this->messageBus->dispatch(new DeleteUser($user->getId()));
            ++$dispatched;
        }

        $this->logger->info('Dispatched user deletions', ['dispatched' => $dispatched]);
    }
}
