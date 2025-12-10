<?php

declare(strict_types=1);

namespace App\Command;

use App\Handler\MailCryptKeyHandler;
use App\Handler\UserAuthenticationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:users:mailcrypt', description: 'Get MailCrypt values for user')]
final class UsersMailCryptCommand extends AbstractUsersCommand
{
    public function __construct(
        EntityManagerInterface $manager,
        private readonly UserAuthenticationHandler $handler,
        private readonly MailCryptKeyHandler $mailCryptKeyHandler,
        #[Autowire(env: 'MAIL_CRYPT')]
        private readonly int $mailCrypt,
    ) {
        parent::__construct($manager);
    }

    #[Override]
    protected function configure(): void
    {
        parent::configure();
        $this
            ->addArgument(
                'password',
                InputOption::VALUE_OPTIONAL,
                'password of supplied email address'
            );
    }

    /**
     * @throws Exception
     */
    #[Override]
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
