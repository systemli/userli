<?php

namespace App\Command;

use App\Repository\VoucherRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author louis <louis@systemli.org>
 */
class MuninVoucherCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('usrmgmt:munin:voucher')
            ->setDescription('Munin plugin for vouchers')
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
            $output->writeln('graph_title User Vouchers');
            $output->writeln('graph_category Mail');
            $output->writeln('graph_vlabel Voucher Counters');
            $output->writeln('voucher_total.label Total Vouchers');
            $output->writeln('voucher_total.type GAUGE');
            $output->writeln('voucher_total.min 0');
            $output->writeln('voucher_redeemed.label Redeemed Vouchers');
            $output->writeln('voucher_redeemed.type GAUGE');
            $output->writeln('voucher_redeemed.min 0');

            return;
        }

        $redeemedVouchers = null !== $this->getVoucherRepository()->findAllRedeemedVouchers() ? $this->getVoucherRepository()->findAllRedeemedVouchers()->count() : 0;

        $output->writeln(sprintf('voucher_total.value %d', $this->getVoucherCounter()->getCount()));
        $output->writeln(sprintf('voucher_redeemed.value %d', $redeemedVouchers));
    }

    /**
     * @return VoucherRepository
     */
    private function getVoucherRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('App:Voucher');
    }

    /**
     * @return \App\Counter\VoucherCounter
     */
    private function getVoucherCounter()
    {
        return $this->getContainer()->get('App\Counter\VoucherCounter');
    }
}
