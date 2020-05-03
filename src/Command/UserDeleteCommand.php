<?php

namespace App\Command;

use App\Entity\User;
use App\Handler\DeleteHandler;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserDeleteCommand extends Command
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var DeleteHandler
     */
    private $deleteHandler;

    /**
     * RegistrationMailCommand constructor.
     */
    public function __construct(ObjectManager $manager, DeleteHandler $deleteHandler)
    {
        $this->manager = $manager;
        $this->deleteHandler = $deleteHandler;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:users:delete')
            ->setDescription('Delete a user')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User to delete')
            ->addOption('dry-run', null, InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = $input->getOption('user');

        if (empty($email) || null === $user = $this->manager->getRepository(User::class)->findByEmail($email)) {
            throw new UsernameNotFoundException(sprintf('User with email %s not found!', $email));
        }

        if ($input->getOption('dry-run')) {
            $output->write(sprintf("Would delete user %s\n", $email));
        } else {
            $output->write(sprintf("Deleting user %s\n", $email));
            $this->deleteHandler->deleteUser($user);
        }
    }
}
