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
    const VMAIL_PATH = '/var/vmail/%s/%s';

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * RegistrationMailCommand constructor.
     * @param ObjectManager $manager
     * @param string|null $name
     */
    public function __construct(ObjectManager $manager, ?string $name = null)
    {
        parent::__construct($name);
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:users:remove')
            ->setDescription('Removes all mailboxes from deleted users')
            ->addOption('dry-run', null, InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var User[] $users */
        $users = $this->manager->getRepository('App:User')->findDeletedUsers();
        $filesystem = new Filesystem();

        $output->writeln(sprintf('<info>Found %d users to delete</info>', count($users)));

        foreach ($users as $user) {
            if (null === $user->getDomain()) {
                continue;
            }
            $domain = $user->getDomain()->getName();
            $name = str_replace('@'.$domain, '', $user->getEmail());
            $path = sprintf(self::VMAIL_PATH, $domain, $name);

            if ($input->getOption('dry-run')) {
                $output->writeln(sprintf('Would delete directory for user: %s', $user));

                return;
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
    }
}
