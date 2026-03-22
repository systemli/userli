<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\PasswordMismatchException;
use App\Exception\PasswordPolicyException;
use App\Service\UserRestoreService;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:users:restore', description: 'Restore a user')]
final class UsersRestoreCommand extends AbstractUsersCommand
{
    public function __construct(
        EntityManagerInterface $manager,
        private readonly UserRestoreService $userRestoreService,
    ) {
        parent::__construct($manager);
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->getUser($input, $output);
        if (null === $user) {
            return Command::FAILURE;
        }

        if (!$user->isDeleted()) {
            $output->writeln(sprintf('<error>User with email %s is still active! Consider to reset the user instead.</error>', $user->getEmail()));

            return Command::FAILURE;
        }

        try {
            $password = $this->askForPassword($input, $output);
        } catch (PasswordPolicyException|PasswordMismatchException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        if ($input->getOption('dry-run')) {
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
