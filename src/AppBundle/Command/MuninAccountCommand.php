<?php

namespace AppBundle\Command;

use AppBundle\Counter\UserCounter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author louis <louis@systemli.org>
 */
class MuninAccountCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('usrmgmt:munin:account')
            ->setDescription('Munin plugin for accounts')
            ->addOption('autoconf', null, InputOption::VALUE_NONE, 'autoconf for the plugin')
            ->addOption('config', null, InputOption::VALUE_NONE, 'config for the plugin');
    }

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

        $output->writeln(sprintf('account.value %d', $this->getUserCounter()->getCount()));
    }

    /**
     * @return UserCounter
     */
    private function getUserCounter()
    {
        return $this->getContainer()->get('AppBundle\Counter\UserCounter');
    }
}
