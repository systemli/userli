<?php

declare(strict_types=1);

namespace App\Command;

use App\Mail\WelcomeMailer;
use App\Repository\UserRepository;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsCommand(name: 'app:users:registration:mail', description: 'Send a registration mail to a user')]
final readonly class UsersRegistrationMailCommand
{
    public function __construct(
        private UserRepository $userRepository,
        private WelcomeMailer $welcomeMailer,
        #[Autowire('kernel.default_locale')]
        private string $defaultLocale,
    ) {
    }

    /**
     * @throws Exception
     */
    public function __invoke(
        #[Option(name: 'user', description: 'User who get the voucher(s)', shortcut: 'u')]
        ?string $email = null,
        #[Option(description: 'the locale', shortcut: 'l')]
        ?string $locale = null,
    ): int {
        $locale ??= $this->defaultLocale;

        if (empty($email) || null === $user = $this->userRepository->findByEmail($email)) {
            throw new UserNotFoundException(sprintf('User with email %s not found!', $email));
        }

        $this->welcomeMailer->send($user, $locale);

        return Command::SUCCESS;
    }
}
