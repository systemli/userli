<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\PasswordMismatchException;
use App\Exception\PasswordPolicyException;
use App\Repository\UserRepository;
use App\Service\ConsolePasswordHelper;
use App\Service\UserRestoreService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:users:restore', description: 'Restore a user')]
final readonly class UsersRestoreCommand
{
    public function __construct(
        private UserRepository $userRepository,
        private UserRestoreService $userRestoreService,
        private ConsolePasswordHelper $consolePasswordHelper,
    ) {
    }

    public function __invoke(
        #[Option(name: 'user', description: 'User to act upon', shortcut: 'u')]
        ?string $email = null,
        #[Option(name: 'dry-run', description: 'Simulate without making changes')]
        bool $dryRun = false,
        ?InputInterface $input = null,
        ?OutputInterface $output = null,
    ): int {
        if (empty($email) || null === $user = $this->userRepository->findByEmail($email)) {
            $output->writeln(sprintf('<error>User with email %s not found!</error>', $email));

            return Command::FAILURE;
        }

        if (!$user->isDeleted()) {
            $output->writeln(sprintf('<error>User with email %s is still active! Consider to reset the user instead.</error>', $user->getEmail()));

            return Command::FAILURE;
        }

        try {
            $password = $this->consolePasswordHelper->askForPassword($input, $output);
        } catch (PasswordPolicyException|PasswordMismatchException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        if ($dryRun) {
            $output->write(sprintf("\nWould restore user %s\n\n", $user->getEmail()));

            return Command::SUCCESS;
        }

        $output->write(sprintf("\nRestoring user %s ...\n\n", $user->getEmail()));

        $recoveryToken = $this->userRestoreService->restoreUser($user, $password);

        if ($recoveryToken !== null) {
            $output->write(sprintf("<info>New recovery token (please hand over to user): %s</info>\n\n", $recoveryToken));
        }

        return Command::SUCCESS;
    }
}
