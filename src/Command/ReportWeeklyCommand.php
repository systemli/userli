<?php

declare(strict_types=1);

namespace App\Command;

use App\Handler\UserRegistrationInfoHandler;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:report:weekly', description: 'Send weekly report to all admins')]
final class ReportWeeklyCommand extends Command
{
    public function __construct(private readonly UserRegistrationInfoHandler $handler)
    {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->handler->sendReport();

        return 0;
    }
}
