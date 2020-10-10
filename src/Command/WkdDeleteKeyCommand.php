<?php

namespace App\Command;

use App\Handler\OpenPGPWkdHandler;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WkdDeleteKeyCommand extends Command
{
    /**
     * @var OpenPGPWkdHandler
     */
    private $handler;

    /**
     * @var UserRepository
     */
    private $repository;

    public function __construct(ObjectManager $manager, OpenPGPWkdHandler $handler)
    {
        $this->handler = $handler;
        $this->repository = $manager->getRepository('App:User');
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:users:wkd:delete-key')
            ->setDescription('Delete OpenPGP key for user')
            ->addArgument(
                'email',
                InputOption::VALUE_REQUIRED,
                'email address of the user');
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
            throw new \RuntimeException('User not found: '.$email);
        }

        // Delete the key
        $this->handler->deleteKey($user);

        $output->writeln(sprintf('Deleted WKD key for user %s', $user->getEmail()));
    }
}
