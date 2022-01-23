<?php

namespace App\Command;

use App\Handler\UserRegistrationInfoHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WeeklyReportCommand.
 */
class WeeklyReportCommand extends Command
{
    /**
     * @var UserRegistrationInfoHandler
     */
    private $handler;

    /**
     * WeeklyReportCommand constructor.
     */
    public function __construct(UserRegistrationInfoHandler $handler)
    {
        $this->handler = $handler;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:report:weekly')
            ->setDescription('Send weekly report to all admins');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->handler->sendReport();
    }
}
