<?php

namespace App\Command;

use App\Repository\AliasRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MuninAliasCommand.
 */
class MuninAliasCommand extends Command
{
    /**
     * @var AliasRepository
     */
    private $repository;

    /**
     * MuninAliasCommand constructor.
     *
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        parent::__construct();
        $this->repository = $manager->getRepository('App:Alias');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('usrmgmt:munin:alias')
            ->setDescription('Munin plugin for aliases')
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
            $output->writeln('graph_title User Aliases');
            $output->writeln('graph_category Mail');
            $output->writeln('graph_vlabel Alias Counters');
            $output->writeln('alias_total.label Total Aliases');
            $output->writeln('alias_total.type GAUGE');
            $output->writeln('alias_total.min 0');
            $output->writeln('alias_random.label Random Aliases');
            $output->writeln('alias_random.type GAUGE');
            $output->writeln('alias_random.min 0');

            return;
        }

        $output->writeln(sprintf('alias_total.value %d', $this->repository->count([])));
        $output->writeln(sprintf('alias_random.value %d', $this->repository->count(['random' => true])));
    }
}
