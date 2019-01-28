<?php

namespace App\Command;

use App\Handler\UserAuthenticationHandler;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MailCryptCommand extends Command
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
            ->setName('usrmgmt:users:mailcrypt')
            ->setDescription('Get mail_crypt values for user')
            ->addArgument(
                'email',
                InputOption::VALUE_REQUIRED,
                'email to get mail_crypt values for');
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

        // get mail_crypt values
        if ($user->hasMailCryptPrivateSecret() && $user->hasMailCryptPublicKey()) {
            $mailCryptPrivateKey = $user->getMailCryptPrivateSecret();
            $output->write(sprintf("%s\n%s", $mailCryptPrivateKey, $user->getMailCryptPublicKey()));
        }

        return 0;
    }
}
