<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\PasswordMismatchException;
use App\Exception\PasswordPolicyException;
use App\Helper\AdminPasswordUpdater;
use App\Service\ConsolePasswordHelper;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:admin:password',
    description: 'Set password of admin user',
    help: 'Set password of admin user. Create primary user and domain if do not exist.'
)]
final readonly class AdminPasswordCommand
{
    public function __construct(
        private AdminPasswordUpdater $updater,
        private ConsolePasswordHelper $consolePasswordHelper,
    ) {
    }

    public function __invoke(
        #[Argument(description: 'Admin password (omit for interactive prompt)')]
        ?string $password = null,
        ?InputInterface $input = null,
        ?OutputInterface $output = null,
    ): int {
        try {
            if (null !== $password) {
                $this->consolePasswordHelper->validatePassword($password);
            } else {
                $password = $this->consolePasswordHelper->askForPassword($input, $output);
            }
        } catch (PasswordPolicyException|PasswordMismatchException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        $this->updater->updateAdminPassword($password);

        return Command::SUCCESS;
    }
}
