<?php

namespace App\Command;

use App\Helper\AdminPasswordUpdater;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AdminPasswordCommand extends Command
{
    /**
     * @var ObjectManager
     */
    private $manager;
    /**
     * @var AdminPasswordUpdater
     */
    private $updater;

    /**
     * AdminPasswordCommand constructor.
     */
    public function __construct(ObjectManager $manager, AdminPasswordUpdater $updater)
    {
        parent::__construct();
        $this->manager = $manager;
        $this->updater = $updater;
    }

    public function configure()
    {
        $this
            ->setName('app:admin:password')
            ->setDescription('Set password of admin user')
            ->setHelp('Set password of admin user. Create primary user and domain if not created before.')
            ->addOption('password', 'p', InputArgument::OPTIONAL, 'Admin password');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->hasArgument('password')) {
            $output->writeln('Please enter new admin password:');
            $password = fgets(STDIN);
        } else {
            $password = $input->getArgument('password');
        }
        $output->writeln(trim($password));
        $this->updater->updateAdminPassword(trim($password));
    }
}
