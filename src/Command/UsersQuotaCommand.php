<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:users:quota', description: 'Get quota of user if set')]
class UsersQuotaCommand extends AbstractUsersCommand
{
    protected function configure(): void
    {
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->getUser($input);

        // get quota
        $quota = $user->getQuota();
        if (null === $quota) {
            return 0;
        }

        $output->writeln(sprintf('%u', $quota));

        return 0;
    }
}
