<?php

declare(strict_types=1);

namespace App\Command;

use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:users:quota', description: 'Get quota of user if set')]
final class UsersQuotaCommand extends AbstractUsersCommand
{
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->getUser($input, $output);
        if (null === $user) {
            return Command::FAILURE;
        }

        // get quota
        $quota = $user->getQuota();
        if (null === $quota) {
            return Command::SUCCESS;
        }

        $output->writeln(sprintf('%u', $quota));

        return Command::SUCCESS;
    }
}
