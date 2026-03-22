<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\MailCrypt;
use App\Handler\MailCryptKeyHandler;
use App\Handler\UserAuthenticationHandler;
use App\Repository\UserRepository;
use App\Service\SettingsService;
use Exception;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:users:mailcrypt', description: 'Get MailCrypt values for user')]
final readonly class UsersMailCryptCommand
{
    public function __construct(
        private UserRepository $userRepository,
        private UserAuthenticationHandler $handler,
        private MailCryptKeyHandler $mailCryptKeyHandler,
        private SettingsService $settingsService,
    ) {
    }

    /**
     * @throws Exception
     */
    public function __invoke(
        #[Option(name: 'user', description: 'User to act upon', shortcut: 'u')]
        ?string $email = null,
        #[Argument(description: 'password of supplied email address')]
        ?string $password = null,
        ?OutputInterface $output = null,
    ): int {
        $mailCrypt = MailCrypt::from($this->settingsService->get('mail_crypt'));
        if ($mailCrypt === MailCrypt::DISABLED) {
            return Command::FAILURE;
        }

        if (empty($email) || null === $user = $this->userRepository->findByEmail($email)) {
            $output->writeln(sprintf('<error>User with email %s not found!</error>', $email));

            return Command::FAILURE;
        }

        if (!$user->getMailCryptEnabled() || !$user->hasMailCryptPublicKey() || !$user->hasMailCryptSecretBox()) {
            return Command::FAILURE;
        }

        if ($password) {
            // verify user credentials
            if (null === $user = $this->handler->authenticate($user, $password)) {
                return Command::FAILURE;
            }

            // get MailCrypt private key
            $mailCryptPrivateKey = $this->mailCryptKeyHandler->decrypt($user, $password);

            $output->write(sprintf("%s\n%s", $mailCryptPrivateKey, $user->getMailCryptPublicKey()));
        } else {
            $output->write(sprintf('%s', $user->getMailCryptPublicKey()));
        }

        return Command::SUCCESS;
    }
}
