<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:users:quota')]
class UsersQuotaCommand extends AbstractUsersCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Get quota of user if set');
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
