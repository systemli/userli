<?php

namespace App\Command;

use App\Entity\Alias;
use App\Repository\AliasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MuninAliasCommand.
 */
class MuninAliasCommand extends Command
{
    protected static $defaultName = 'app:munin:alias';
    private readonly AliasRepository $repository;

    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct();
        $this->repository = $manager->getRepository(Alias::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Munin plugin for aliases')
            ->addOption('autoconf', null, InputOption::VALUE_NONE, 'autoconf for the plugin')
            ->addOption('config', null, InputOption::VALUE_NONE, 'config for the plugin');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('autoconf')) {
            $output->writeln('yes');

            return 0;
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

            return 0;
        }

        $output->writeln(sprintf('alias_total.value %d', $this->repository->count([])));
        $output->writeln(sprintf('alias_random.value %d', $this->repository->count(['random' => true])));

        return 0;
    }
}
