<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

abstract class AbstractUsersCommand extends Command
{
    public function __construct(
        protected readonly EntityManagerInterface $manager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User to act upon')
            ->addOption('dry-run', null, InputOption::VALUE_NONE);
    }

    /**
     * @throws UserNotFoundException
     */
    protected function getUser(InputInterface $input): User
    {
        $email = $input->getOption('user');
        if (empty($email) || null === $user = $this->manager->getRepository(User::class)->findByEmail($email)) {
            throw new UserNotFoundException(sprintf('User with email %s not found!', $email));
        }

        return $user;
    }
}
