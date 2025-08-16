<?php

declare(strict_types=1);

namespace App\Command;

use Exception;
use App\Repository\UserNotificationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:notification:cleanup',
    description: 'Clean up old user notifications'
)]
class UserNotificationCleanupCommand extends Command
{
    public function __construct(
        private readonly UserNotificationRepository $repository
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Number of days to keep notifications (default: 30)',
                30
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                'Notification type to clean up (if not specified, all types)',
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int)$input->getOption('days');
        $type = $input->getOption('type');

        if ($days < 1) {
            $io->error('Days must be a positive number');
            return Command::FAILURE;
        }

        if ($type) {
            $io->info(sprintf('Cleaning up %s notifications older than %d days...', $type, $days));
        } else {
            $io->info(sprintf('Cleaning up all notifications older than %d days...', $days));
        }

        try {
            $deletedCount = $this->repository->cleanupOldNotifications($days, $type);

            if ($type) {
                $io->success(sprintf('Successfully deleted %d old %s notification records', $deletedCount, $type));
            } else {
                $io->success(sprintf('Successfully deleted %d old notification records', $deletedCount));
            }

            return Command::SUCCESS;

        } catch (Exception $exception) {
            $io->error(sprintf('Error during cleanup: %s', $exception->getMessage()));
            return Command::FAILURE;
        }
    }
}
