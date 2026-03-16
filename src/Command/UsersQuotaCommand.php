<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:users:quota', description: 'Get quota of user if set')]
final readonly class UsersQuotaCommand
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(
        #[Option(name: 'user', description: 'User to act upon', shortcut: 'u')]
        ?string $email = null,
        ?OutputInterface $output = null,
    ): int {
        if (empty($email) || null === $user = $this->userRepository->findByEmail($email)) {
            $output->writeln(sprintf('<error>User with email %s not found!</error>', $email));

            return Command::FAILURE;
        }

        $quota = $user->getQuota();
        if (null === $quota) {
            return Command::SUCCESS;
        }

        $output->writeln(sprintf('%u', $quota));

        return Command::SUCCESS;
    }
}
