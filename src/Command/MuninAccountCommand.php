<?php

namespace App\Command;

use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Repository\OpenPgpKeyRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MuninAccountCommand.
 */
class MuninAccountCommand extends Command
{
    protected static $defaultName = 'app:munin:account';
    private readonly UserRepository $userRepository;
    private readonly OpenPgpKeyRepository $openPgpKeyRepository;

    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct();
        $this->userRepository = $manager->getRepository(User::class);
        $this->openPgpKeyRepository = $manager->getRepository(OpenPgpKey::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Munin plugin for accounts')
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
            $output->writeln('graph_title User Accounts');
            $output->writeln('graph_category Mail');
            $output->writeln('graph_vlabel Account Counters');
            $output->writeln('account.label Active accounts');
            $output->writeln('account.type GAUGE');
            $output->writeln('account.min 0');
            $output->writeln('deleted.label Deleted accounts');
            $output->writeln('deleted.type GAUGE');
            $output->writeln('deleted.min 0');
            $output->writeln('recovery_tokens.label Active accounts with recovery token');
            $output->writeln('recovery_tokens.type GAUGE');
            $output->writeln('recovery_tokens.min 0');
            $output->writeln('mail_crypt_keys.label Active accounts with mailbox encryption');
            $output->writeln('mail_crypt_keys.type GAUGE');
            $output->writeln('mail_crypt_keys.min 0');
            $output->writeln('twofactor.label Active accounts with two-factor authentication');
            $output->writeln('twofactor.type GAUGE');
            $output->writeln('twofactor.min 0');
            $output->writeln('openpgp_keys.label OpenPGP keys');
            $output->writeln('openpgp_keys.type GAUGE');
            $output->writeln('openpgp_keys.min 0');

            return 0;
        }

        $output->writeln(sprintf('account.value %d', $this->userRepository->countUsers()));
        $output->writeln(sprintf('deleted.value %d', $this->userRepository->countDeletedUsers()));
        $output->writeln(sprintf('recovery_tokens.value %d', $this->userRepository->countUsersWithRecoveryToken()));
        $output->writeln(sprintf('mail_crypt_keys.value %d', $this->userRepository->countUsersWithMailCrypt()));
        $output->writeln(sprintf('twofactor.value %d', $this->userRepository->countUsersWithTwofactor()));
        $output->writeln(sprintf('openpgp_keys.value %d', $this->openPgpKeyRepository->countKeys()));

        return 0;
    }
}
