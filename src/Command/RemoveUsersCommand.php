<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class RemoveUsersCommand extends Command
{
    /**
     * @var ObjectManager
     */
    private $manager;
    /**
     * @var string
     */
    private $mailLocation;

    /**
     * RegistrationMailCommand constructor.
     */
    public function __construct(ObjectManager $manager,
                                string $mailLocation,
                                ?string $name = null)
    {
        parent::__construct($name);
        $this->manager = $manager;
        $this->mailLocation = $mailLocation;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:users:remove')
            ->setDescription('Removes all mailboxes from deleted users')
            ->addOption('dry-run', null, InputOption::VALUE_NONE)
            ->addOption('list', null, InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var User[] $users */
        $users = $this->manager->getRepository('App:User')->findDeletedUsers();
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
