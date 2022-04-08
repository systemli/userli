<?php

namespace App\Command;

use App\Handler\MailCryptKeyHandler;
use App\Handler\UserAuthenticationHandler;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UsersMailCryptCommand extends Command
{
    /**
     * @var UserAuthenticationHandler
     */
    private $handler;
    /**
     * @var MailCryptKeyHandler
     */
    private $mailCryptKeyHandler;
    /**
     * @var UserRepository
     */
    private $repository;
    /**
     * @var int
     */
    private $mailCrypt;

    public function __construct(
        EntityManagerInterface $manager,
        UserAuthenticationHandler $handler,
        MailCryptKeyHandler $mailCryptKeyHandler,
        int $mailCrypt
    ) {
        $this->handler = $handler;
        $this->repository = $manager->getRepository('App:User');
        $this->mailCryptKeyHandler = $mailCryptKeyHandler;
        $this->mailCrypt = $mailCrypt;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:users:mailcrypt')
            ->setDescription('Get MailCrypt values for user')
            ->addArgument(
                'email',
                InputOption::VALUE_REQUIRED,
                'email to get MailCrypt values for')
            ->addArgument(
                'password',
                InputOption::VALUE_OPTIONAL,
                'password of supplied email address');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->mailCrypt <= 0) {
            return 1;
        }

        // parse arguments
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        // Check if user exists
        $user = $this->repository->findByEmail($email);

        if (null === $user || !$user->hasMailCrypt() || !$user->hasMailCryptPublicKey() || !$user->hasMailCryptSecretBox()) {
            return 1;
        }

        if ($password) {
            $password = $password[0];
            // verify user credentials
            if (null === $user = $this->handler->authenticate($user, $password)) {
                return 1;
            }

            // get MailCrypt private key
            $mailCryptPrivateKey = $this->mailCryptKeyHandler->decrypt($user, $password);

            $output->write(sprintf("%s\n%s", $mailCryptPrivateKey, $user->getMailCryptPublicKey()));
        } else {
            $output->write(sprintf('%s', $user->getMailCryptPublicKey()));
        }

        return 0;
    }
}
