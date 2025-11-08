<?php

declare(strict_types=1);

namespace App\Command;

use App\Handler\DeleteHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:users:delete', description: 'Delete a user')]
class UsersDeleteCommand extends AbstractUsersCommand
{
    public function __construct(
        EntityManagerInterface $manager,
        private readonly DeleteHandler $deleteHandler,
    ) {
        parent::__construct($manager);
    }

    protected function configure(): void
    {
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->getUser($input);

        if ($input->getOption('dry-run')) {
            $output->write(sprintf("Would delete user %s\n", $user->getEmail()));
        } else {
            $output->write(sprintf("Deleting user %s\n", $user->getEmail()));
            $this->deleteHandler->deleteUser($user);
        }

        return 0;
    }
}
