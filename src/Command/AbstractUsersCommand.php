<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractUsersCommand extends Command
{
    use PasswordPromptTrait;

    public function __construct(
        protected readonly EntityManagerInterface $manager,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User to act upon')
            ->addOption('dry-run', null, InputOption::VALUE_NONE);
    }

    protected function getUser(InputInterface $input, OutputInterface $output): ?User
    {
        $email = $input->getOption('user');
        if (empty($email) || null === $user = $this->manager->getRepository(User::class)->findByEmail($email)) {
            $output->writeln(sprintf('<error>User with email %s not found!</error>', $email));

            return null;
        }

        return $user;
    }
}
