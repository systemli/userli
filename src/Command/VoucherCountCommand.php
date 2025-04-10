<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Voucher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsCommand(name: 'app:voucher:count')]
class VoucherCountCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Get count of vouchers for a specific user')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User whose vouchers are counted');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getOption('user');

        if (empty($email) || null === $user = $this->manager->getRepository(User::class)->findByEmail($email)) {
            throw new UserNotFoundException(sprintf('User with email %s not found!', $email));
        }

        $usedCount = $this->manager->getRepository(Voucher::class)->countVouchersByUser($user, true);
        $unusedCount = $this->manager->getRepository(Voucher::class)->countVouchersByUser($user, false);
        $output->writeln(sprintf("Voucher count for user %s", $user->getEmail()));
        $output->writeln(sprintf("Used: %d", $usedCount));
        $output->writeln(sprintf("Unused: %d", $unusedCount));

        return 0;
    }
}
