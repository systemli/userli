<?php

declare(strict_types=1);

namespace App\Command;

use App\Handler\DeleteHandler;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:users:delete', description: 'Delete a user')]
final class UsersDeleteCommand extends AbstractUsersCommand
{
    public function __construct(
        EntityManagerInterface $manager,
        private readonly DeleteHandler $deleteHandler,
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

        if ($input->getOption('dry-run')) {
            $output->write(sprintf("Would delete user %s\n", $user->getEmail()));
        } else {
            $output->write(sprintf("Deleting user %s\n", $user->getEmail()));
            $this->deleteHandler->deleteUser($user);
        }

        return Command::SUCCESS;
    }
}
