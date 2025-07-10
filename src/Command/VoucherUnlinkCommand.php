<?php

namespace App\Command;

use App\Entity\Voucher;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:voucher:unlink', description: 'Remove connection between vouchers and accounts after 3 months')]
class VoucherUnlinkCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry run, without any changes');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $vouchers = $this->manager->getRepository(Voucher::class)->getOldVouchers();

        $output->writeln(
            sprintf('<INFO>unlink %d vouchers</INFO>', count($vouchers)),
            OutputInterface::VERBOSITY_VERBOSE
        );

        foreach ($vouchers as $voucher) {
            $output->writeln(
                sprintf(
                    '%d: %s (%s)',
                    $voucher->getId(),
                    $voucher->getCode(),
                    $voucher->getRedeemedTime()->format(DateTimeInterface::W3C)
                ),
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );
        }

        if (false === $input->getOption('dry-run')) {
            $this->manager->flush();
        }

        return 0;
    }
}
