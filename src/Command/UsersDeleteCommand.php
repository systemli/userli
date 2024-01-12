<?php

namespace App\Command;

use App\Entity\User;
use App\Handler\DeleteHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class UsersDeleteCommand extends Command
{
    protected static $defaultName = 'app:users:delete';
    public function __construct(private EntityManagerInterface $manager, private DeleteHandler $deleteHandler)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Delete a user')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User to delete')
            ->addOption('dry-run', null, InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getOption('user');

        if (empty($email) || null === $user = $this->manager->getRepository(User::class)->findByEmail($email)) {
            throw new UserNotFoundException(sprintf('User with email %s not found!', $email));
        }

        if ($input->getOption('dry-run')) {
            $output->write(sprintf("Would delete user %s\n", $email));
        } else {
            $output->write(sprintf("Deleting user %s\n", $email));
            $this->deleteHandler->deleteUser($user);
        }

        return 0;
    }
}
