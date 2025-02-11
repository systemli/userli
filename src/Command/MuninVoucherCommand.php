<?php

namespace App\Command;

use App\Entity\Voucher;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated Use Prometheus exporter instead.
 *
 * Class MuninVoucherCommand.
 */
#[AsCommand(name: 'app:munin:voucher')]
class MuninVoucherCommand extends Command
{
    private readonly VoucherRepository $repository;

    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct();
        $this->repository = $manager->getRepository(Voucher::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Munin plugin for vouchers')
            ->addOption('autoconf', null, InputOption::VALUE_NONE, 'autoconf for the plugin')
            ->addOption('config', null, InputOption::VALUE_NONE, 'config for the plugin');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        trigger_error("MuninVoucherCommand is deprecated. Use Prometheus exporter instead.", E_USER_DEPRECATED);

        if ($input->getOption('autoconf')) {
            $output->writeln('yes');

            return 0;
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

            return 0;
        }

        $output->writeln(sprintf('voucher_total.value %d', $this->repository->count([])));
        $output->writeln(sprintf('voucher_redeemed.value %d', $this->repository->countRedeemedVouchers()));

        return 0;
    }
}
