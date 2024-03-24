<?php

namespace App\Command;

use App\Helper\AdminPasswordUpdater;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(name: 'app:admin:password')]
class AdminPasswordCommand extends Command
{
    /**
     * AdminPasswordCommand constructor.
     */
    public function __construct(private readonly AdminPasswordUpdater $updater)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setDescription('Set password of admin user')
            ->setHelp('Set password of admin user. Create primary user and domain if not created before.')
            ->addArgument('password', InputArgument::OPTIONAL, 'Admin password');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $password = $input->getArgument('password');
        if (null === $password) {
            $helper = $this->getHelper('question');
            $question = new Question('Please enter new admin password:');
            $password = $helper->ask($input, $output, $question);
        }
        $this->updater->updateAdminPassword($password);

        return 0;
    }
}
