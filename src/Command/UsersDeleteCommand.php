<?php

declare(strict_types=1);

namespace App\Command;

use App\Handler\DeleteHandler;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:users:delete', description: 'Delete a user')]
final readonly class UsersDeleteCommand
{
    public function __construct(
        private UserRepository $userRepository,
        private DeleteHandler $deleteHandler,
    ) {
    }

    public function __invoke(
        #[Option(name: 'user', description: 'User to act upon', shortcut: 'u')]
        ?string $email = null,
        #[Option(name: 'dry-run', description: 'Simulate without making changes')]
        bool $dryRun = false,
        ?OutputInterface $output = null,
    ): int {
        if (empty($email) || null === $user = $this->userRepository->findByEmail($email)) {
            $output->writeln(sprintf('<error>User with email %s not found!</error>', $email));

            return Command::FAILURE;
        }

        if ($dryRun) {
            $output->write(sprintf("Would delete user %s\n", $user->getEmail()));
        } else {
            $output->write(sprintf("Deleting user %s\n", $user->getEmail()));
            $this->deleteHandler->deleteUser($user);
        }

        return Command::SUCCESS;
    }
}
