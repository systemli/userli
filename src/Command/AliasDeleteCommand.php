<?php

namespace App\Command;

use App\Entity\Alias;
use App\Entity\User;
use App\Handler\DeleteHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:alias:delete')]
class AliasDeleteCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $manager, private readonly DeleteHandler $deleteHandler)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Delete an alias')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User who owns the alias (optional)')
            ->addOption('alias', 'a', InputOption::VALUE_REQUIRED, 'Alias address to delete')
            ->addOption('dry-run', null, InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getOption('user');
        $source = $input->getOption('alias');

        $user = null;
        if ($email && null === $user = $this->manager->getRepository(User::class)->findByEmail($email)) {
            $output->writeln(sprintf("<error>User with email '%s' not found!</error>", $email));
            return 1;
        }

        if (empty($source) || null === $alias = $this->manager->getRepository(Alias::class)->findOneBySource($source)) {
            $output->writeln(sprintf("<error>Alias with address '%s' not found!</error>", $source));
            return 1;
        }

        if ($input->getOption('dry-run')) {
            if ($user) {
                $output->write(sprintf("Would delete alias %s of user %s\n", $source, $email));
            } else {
                $output->write(sprintf("Would delete alias %s\n", $source));
            }
        } else {
            if ($user) {
                $output->write(sprintf("Deleting alias %s of user %s\n", $source, $email));
                $this->deleteHandler->deleteAlias($alias, $user);
            } else {
                $output->write(sprintf("Deleting alias %s\n", $source));
                $this->deleteHandler->deleteAlias($alias);
            }
        }

        return 0;
    }
}
