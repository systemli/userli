<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MuninAccountCommand.
 */
class MuninAccountCommand extends Command
{
    /**
     * @var UserRepository
     */
    private $repository;

    /**
     * MuninAccountCommand constructor.
     */
    public function __construct(ObjectManager $manager)
    {
        parent::__construct();
        $this->repository = $manager->getRepository('App:User');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:munin:account')
            ->setDescription('Munin plugin for accounts')
            ->addOption('autoconf', null, InputOption::VALUE_NONE, 'autoconf for the plugin')
            ->addOption('config', null, InputOption::VALUE_NONE, 'config for the plugin');
    }

    /**
     * {@inheritdoc}
     */
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
            $output->writeln('wkd_keys.label Active accounts with WKD key');
            $output->writeln('wkd_keys.type GAUGE');
            $output->writeln('wkd_keys.min 0');

            return;
        }

        $output->writeln(sprintf('account.value %d', $this->repository->countUsers()));
        $output->writeln(sprintf('deleted.value %d', $this->repository->countDeletedUsers()));
        $output->writeln(sprintf('recovery_tokens.value %d', $this->repository->countUsersWithRecoveryToken()));
        $output->writeln(sprintf('mail_crypt_keys.value %d', $this->repository->countUsersWithMailCrypt()));
        $output->writeln(sprintf('wkd_keys.value %d', $this->repository->countUsersWithWkdKey()));
    }
}
