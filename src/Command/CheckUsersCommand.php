<?php

namespace App\Command;

use App\Enum\Roles;
use App\Handler\UserAuthenticationHandler;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckUsersCommand extends Command
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var UserAuthenticationHandler
     */
    private $handler;

    /**
     * @var UserRepository
     */
    private $repository;

    public function __construct(ObjectManager $manager, UserAuthenticationHandler $handler)
    {
        $this->manager = $manager;
        $this->handler = $handler;
        $this->repository = $this->manager->getRepository('App:User');
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:users:check')
            ->setDescription('Check if user is present')
            ->addArgument(
                'email',
                InputOption::VALUE_REQUIRED,
                'email to test presence of')
            ->addArgument('password',
                InputOption::VALUE_OPTIONAL,
                'password of supplied email address');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // parse arguments
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        // Check if user exists
        $user = $this->repository->findByEmail($email);

        // test password
        if ($password && null !== $user) {
            $password = $password[0];

            // spammers not allowed to authenticate via checkpassword
            if ($user->hasRole(Roles::SPAM)) {
                $user = null;
            } else {
                $user = $this->handler->authenticate($user, $password);
            }
        }

        // exit if user not present or not authenticated
        if (null === $user) {
            $output->writeln('FAIL');

            return 1;
        }
        $output->writeln('OK');
    }
}
