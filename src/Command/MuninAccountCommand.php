<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MuninAccountCommand.
 */
class MuninAccountCommand extends Command
{
    /**
     * @var UserRepository
     */
    private $repository;

    /**
     * MuninAccountCommand constructor.
     *
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        parent::__construct();
        $this->repository = $manager->getRepository('App:User');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:munin:account')
            ->setDescription('Munin plugin for accounts')
            ->addOption('autoconf', null, InputOption::VALUE_NONE, 'autoconf for the plugin')
            ->addOption('config', null, InputOption::VALUE_NONE, 'config for the plugin');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('autoconf')) {
            $output->writeln('yes');

            return;
        }

        if ($input->getOption('config')) {
            $output->writeln('graph_title User Accounts');
            $output->writeln('graph_category Mail');
            $output->writeln('graph_vlabel Account Counters');
            $output->writeln('account.label Total Accounts');
            $output->writeln('account.type GAUGE');
            $output->writeln('account.min 0');

            return;
        }

        $output->writeln(sprintf('account.value %d', $this->repository->count([])));
    }
}
