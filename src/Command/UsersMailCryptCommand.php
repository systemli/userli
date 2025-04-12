<?php

namespace App\Command;

use Exception;
use App\Handler\MailCryptKeyHandler;
use App\Handler\UserAuthenticationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:users:mailcrypt')]
class UsersMailCryptCommand extends AbstractUsersCommand
{
    public function __construct(
        EntityManagerInterface                     $manager,
        private readonly UserAuthenticationHandler $handler,
        private readonly MailCryptKeyHandler       $mailCryptKeyHandler,
        private readonly int                       $mailCrypt
    )
    {
        parent::__construct($manager);
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setDescription('Get MailCrypt values for user')
            ->addArgument(
                'password',
                InputOption::VALUE_OPTIONAL,
                'password of supplied email address');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->mailCrypt <= 0) {
            return 1;
        }

        // parse arguments
        $password = $input->getArgument('password');

        // Check if user exists
        $user = $this->getUser($input);
        if (!$user->getMailCryptEnabled() || !$user->hasMailCryptPublicKey() || !$user->hasMailCryptSecretBox()) {
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
