<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Voucher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:voucher:count', description: 'Get count of vouchers for a specific user')]
class VoucherCountCommand extends AbstractUsersCommand
{
    protected function configure(): void
    {
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->getUser($input);

        $usedCount = $this->manager->getRepository(Voucher::class)->countVouchersByUser($user, true);
        $unusedCount = $this->manager->getRepository(Voucher::class)->countVouchersByUser($user, false);
        $output->writeln(sprintf('Voucher count for user %s', $user->getEmail()));
        $output->writeln(sprintf('Used: %d', $usedCount));
        $output->writeln(sprintf('Unused: %d', $unusedCount));

        return 0;
    }
}
