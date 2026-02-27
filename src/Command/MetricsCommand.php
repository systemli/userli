<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\AliasRepository;
use App\Repository\DomainRepository;
use App\Repository\OpenPgpKeyRepository;
use App\Repository\UserRepository;
use App\Repository\VoucherRepository;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MetricsCommand.
 *
 * This command exposes metrics for Prometheus. It is intended to be used as a
 * cronjob. It can be used together with the Prometheus Node Exporter (textfile
 * collector).
 *
 * Example for Cron:
 * * * * * * php /path/to/bin/console app:metrics | sponge /path/to/metrics/userli.prom
 */
#[AsCommand(name: 'app:metrics', description: 'Global Metrics for Userli')]
final class MetricsCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly VoucherRepository $voucherRepository,
        private readonly DomainRepository $domainRepository,
        private readonly AliasRepository $aliasRepository,
        private readonly OpenPgpKeyRepository $openPgpKeyRepository,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $activeUsersTotal = $this->userRepository->countUsers();
        $deletedUsersTotal = $this->userRepository->countDeletedUsers();
        $usersTotal = $activeUsersTotal + $deletedUsersTotal;

        $output->writeln('# HELP userli_users_total Total number of users');
        $output->writeln('# TYPE userli_users_total gauge');
        $output->writeln('userli_users_total '.$usersTotal);

        $output->writeln('# HELP userli_users_active_total Total number of active users');
        $output->writeln('# TYPE userli_users_active_total gauge');
        $output->writeln('userli_users_active_total '.$activeUsersTotal);

        $output->writeln('# HELP userli_users_deleted_total Total number of deleted users');
        $output->writeln('# TYPE userli_users_deleted_total gauge');
        $output->writeln('userli_users_deleted_total '.$deletedUsersTotal);

        $usersRecoveryTokenTotal = $this->userRepository->countUsersWithRecoveryToken();
        $output->writeln('# HELP userli_users_recovery_token_total Total number of users with recovery token');
        $output->writeln('# TYPE userli_users_recovery_token_total gauge');
        $output->writeln('userli_users_recovery_token_total '.$usersRecoveryTokenTotal);

        $usersMailCryptTotal = $this->userRepository->countUsersWithMailCrypt();
        $output->writeln('# HELP userli_users_mailcrypt_total Total number of users with enabled mailcrypt');
        $output->writeln('# TYPE userli_users_mailcrypt_total gauge');
        $output->writeln('userli_users_mailcrypt_total '.$usersMailCryptTotal);

        $usersTwofactorTotal = $this->userRepository->countUsersWithTwofactor();
        $output->writeln('# HELP userli_users_twofactor_total Total number of users with enabled two factor authentication');
        $output->writeln('# TYPE userli_users_twofactor_total gauge');
        $output->writeln('userli_users_twofactor_total '.$usersTwofactorTotal);

        $redeemedVouchersTotal = $this->voucherRepository->countRedeemedVouchers();
        $unredeemedVouchersTotal = $this->voucherRepository->countUnredeemedVouchers();
        $output->writeln('# HELP userli_vouchers_total Total number of vouchers');
        $output->writeln('# TYPE userli_vouchers_total gauge');
        $output->writeln('userli_vouchers_total{type="unredeemed"} '.$unredeemedVouchersTotal);
        $output->writeln('userli_vouchers_total{type="redeemed"} '.$redeemedVouchersTotal);

        $domainsTotal = $this->domainRepository->count([]);
        $output->writeln('# HELP userli_domains_total Total number of domains');
        $output->writeln('# TYPE userli_domains_total gauge');
        $output->writeln('userli_domains_total '.$domainsTotal);

        $aliasesTotal = $this->aliasRepository->count(['deleted' => false]);
        $output->writeln('# HELP userli_aliases_total Total number of aliases');
        $output->writeln('# TYPE userli_aliases_total gauge');
        $output->writeln('userli_aliases_total '.$aliasesTotal);

        $openPgpKeysTotal = $this->openPgpKeyRepository->countKeys();
        $output->writeln('# HELP userli_openpgpkeys_total Total number of OpenPGP keys');
        $output->writeln('# TYPE userli_openpgpkeys_total gauge');
        $output->writeln('userli_openpgpkeys_total '.$openPgpKeysTotal);

        return 0;
    }
}
