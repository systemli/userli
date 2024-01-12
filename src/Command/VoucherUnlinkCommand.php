<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Handler\SuspiciousChildrenHandler;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class VoucherUnlinkCommand extends Command
{
    protected static $defaultName = 'app:voucher:unlink';
    public function __construct(private EntityManagerInterface $manager, private SuspiciousChildrenHandler $handler)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry run, without any changes')
            ->setDescription('Remove connection between vouchers and accounts after 3 months');
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

        $suspiciousChildren = $this->getSuspiciousChildren($vouchers);
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

        if (count($suspiciousChildren) > 0) {
            // output all children of suspicious users
            foreach ($suspiciousChildren as $child => $parent) {
                $output->writeln(
                    sprintf(
                        '<comment>Suspicious User %s has invited %s.</comment>',
                        $parent,
                        $child
                    ),
                    OutputInterface::VERBOSITY_VERBOSE
                );
            }

            // inform about suspicious children via mail
            $this->handler->sendReport($suspiciousChildren);
        }

        if (false === $input->getOption('dry-run')) {
            $this->manager->flush();
        }

        return 0;
    }

    /**
     * @param Voucher[] $vouchers
     *
     * @return string[]
     */
    public function getSuspiciousChildren(array $vouchers): array
    {
        $suspiciousChildren = [];

        foreach ($vouchers as $voucher) {
            if ($voucher instanceof Voucher) {
                $user = $voucher->getInvitedUser();
                if ($user instanceof User) {
                    $user->setInvitationVoucher(null);

                    // check if user was suspicious and has redeemed codes
                    $parent = $voucher->getUser();
                    if ($parent instanceof User && $parent->hasRole(Roles::SUSPICIOUS)) {
                        $suspiciousChildren[$user->getUserIdentifier()] = $parent->getUserIdentifier();
                    }
                }
            }
        }

        return $suspiciousChildren;
    }
}
