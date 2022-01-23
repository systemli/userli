<?php

namespace App\Command;

use App\Repository\VoucherRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MuninVoucherCommand.
 */
class MuninVoucherCommand extends Command
{
    /**
     * @var VoucherRepository
     */
    private $repository;

    public function __construct(ObjectManager $manager)
    {
        parent::__construct();
        $this->repository = $manager->getRepository('App:Voucher');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:munin:voucher')
            ->setDescription('Munin plugin for vouchers')
            ->addOption('autoconf', null, InputOption::VALUE_NONE, 'autoconf for the plugin')
            ->addOption('config', null, InputOption::VALUE_NONE, 'config for the plugin');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
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

        $output->writeln(sprintf('voucher_total.value %d', $this->repository->count([])));
        $output->writeln(sprintf('voucher_redeemed.value %d', $this->repository->countRedeemedVouchers()));
    }
}
