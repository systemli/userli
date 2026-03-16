<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use App\Repository\VoucherRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:voucher:count', description: 'Get count of vouchers for a specific user')]
final readonly class VoucherCountCommand
{
    public function __construct(
        private UserRepository $userRepository,
        private VoucherRepository $voucherRepository,
    ) {
    }

    public function __invoke(
        #[Option(name: 'user', description: 'User to act upon', shortcut: 'u')]
        ?string $email = null,
        ?OutputInterface $output = null,
    ): int {
        if (empty($email) || null === $user = $this->userRepository->findByEmail($email)) {
            $output->writeln(sprintf('<error>User with email %s not found!</error>', $email));

            return Command::FAILURE;
        }

        $usedCount = $this->voucherRepository->countVouchersByUser($user, true);
        $unusedCount = $this->voucherRepository->countVouchersByUser($user, false);
        $output->writeln(sprintf('Voucher count for user %s', $user->getEmail()));
        $output->writeln(sprintf('Used: %d', $usedCount));
        $output->writeln(sprintf('Unused: %d', $unusedCount));

        return Command::SUCCESS;
    }
}
