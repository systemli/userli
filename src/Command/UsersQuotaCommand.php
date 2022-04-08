<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UsersQuotaCommand extends Command
{
    /**
     * @var UserRepository
     */
    private $repository;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->repository = $manager->getRepository('App:User');
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:users:quota')
            ->setDescription('Get quota of user if set')
            ->addArgument(
                'email',
                InputOption::VALUE_REQUIRED,
                'email to get quota of');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // parse arguments
        $email = $input->getArgument('email');

        // Check if user exists
        $user = $this->repository->findByEmail($email);

        if (null === $user) {
            return 1;
        }

        // get quota
        $quota = $user->getQuota();

        if (null === $quota) {
            return 0;
        }

        $output->writeln(sprintf('%u', $quota));
    }
}
