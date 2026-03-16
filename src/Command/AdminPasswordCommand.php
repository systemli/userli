<?php

declare(strict_types=1);

namespace App\Command;

use App\Helper\AdminPasswordUpdater;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:admin:password',
    description: 'Set password of admin user',
    help: 'Set password of admin user. Create primary user and domain if do not exist.'
)]
final readonly class AdminPasswordCommand
{
    public function __construct(private AdminPasswordUpdater $updater)
    {
    }

    public function __invoke(
        #[Argument(description: 'Admin password')]
        ?string $password = null,
        ?InputInterface $input = null,
        ?OutputInterface $output = null,
    ): int {
        if (null === $password) {
            $io = new SymfonyStyle($input, $output);
            $question = new Question('Please enter new admin password:');
            $password = $io->askQuestion($question);
        }

        $this->updater->updateAdminPassword($password);

        return Command::SUCCESS;
    }
}
