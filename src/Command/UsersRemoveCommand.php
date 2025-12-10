<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

use const DIRECTORY_SEPARATOR;

#[AsCommand(name: 'app:users:remove', description: 'Removes all mailboxes from deleted users')]
final class UsersRemoveCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        #[Autowire(env: 'DOVECOT_MAIL_LOCATION')]
        private readonly string $mailLocation,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE)
            ->addOption('list', null, InputOption::VALUE_NONE);
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var User[] $users */
        $users = $this->manager->getRepository(User::class)->findDeletedUsers();
        $filesystem = new Filesystem();

        if (!$input->getOption('list')) {
            $output->writeln(sprintf('<info>Found %d users to delete</info>', count($users)));
        }

        foreach ($users as $user) {
            if (null === $user->getDomain()) {
                continue;
            }

            $domain = $user->getDomain()->getName();
            $name = str_replace('@'.$domain, '', $user->getEmail());
            $path = $this->mailLocation.DIRECTORY_SEPARATOR.$domain.DIRECTORY_SEPARATOR.$name;

            if ($input->getOption('dry-run')) {
                $output->writeln(sprintf('Would delete directory for user: %s', $user));
                continue;
            }

            if ($input->getOption('list')) {
                $output->writeln($path);
                continue;
            }

            if ($filesystem->exists($path)) {
                $output->writeln(sprintf('Delete directory for user: %s', $user));

                try {
                    $filesystem->remove($path);
                } catch (IOException $e) {
                    $output->writeln('<error>'.$e->getMessage().'</error>');
                }
            } else {
                $output->writeln(sprintf('Directory for user does not exist: %s', $user));
            }
        }

        return 0;
    }
}
