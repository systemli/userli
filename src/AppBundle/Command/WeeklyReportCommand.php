<?php

namespace AppBundle\Command;

use AppBundle\Handler\UserRegistrationInfoHandler;
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
     *
     * @param UserRegistrationInfoHandler $handler
     */
    public function __construct(UserRegistrationInfoHandler $handler)
    {
        $this->handler = $handler;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('usrmgmt:report:weekly')
            ->setDescription('Send weekly report to all admins');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->handler->sendReport();
    }
}
