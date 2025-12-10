<?php

declare(strict_types=1);

namespace App\Command;

use App\Helper\AdminPasswordUpdater;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(name: 'app:admin:password', description: 'Set password of admin user')]
class AdminPasswordCommand extends Command
{
    /**
     * AdminPasswordCommand constructor.
     */
    public function __construct(private readonly AdminPasswordUpdater $updater)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->setHelp('Set password of admin user. Create primary user and domain if not created before.')
            ->addArgument('password', InputArgument::OPTIONAL, 'Admin password');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $password = $input->getArgument('password');
        if (null === $password) {
            $helper = $this->getHelper('question');
            assert($helper instanceof QuestionHelper);
            $question = new Question('Please enter new admin password:');
            $password = $helper->ask($input, $output, $question);
        }

        $this->updater->updateAdminPassword($password);

        return 0;
    }
}
