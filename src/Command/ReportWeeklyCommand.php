<?php

namespace App\Command;

use App\Handler\UserRegistrationInfoHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WeeklyReportCommand.
 */
#[AsCommand(name: 'app:report:weekly')]
class ReportWeeklyCommand extends Command
{
    public function __construct(private readonly UserRegistrationInfoHandler $handler)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Send weekly report to all admins');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->handler->sendReport();

        return 0;
    }
}
