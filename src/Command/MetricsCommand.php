<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Entity\Voucher;
use Doctrine\ORM\EntityManagerInterface;
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
class MetricsCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $usersTotal = $this->manager->getRepository(User::class)->countUsers();
        $output->writeln('# HELP userli_users_total Total number of users');
        $output->writeln('# TYPE userli_users_total gauge');
        $output->writeln('userli_users_total '.$usersTotal);

        $deletedUsersTotal = $this->manager->getRepository(User::class)->countDeletedUsers();
        $output->writeln('# HELP userli_users_deleted_total Total number of deleted users');
        $output->writeln('# TYPE userli_users_deleted_total gauge');
        $output->writeln('userli_users_deleted_total '.$deletedUsersTotal);

        $usersRecoveryTokenTotal = $this->manager->getRepository(User::class)->countUsersWithRecoveryToken();
        $output->writeln('# HELP userli_users_recovery_token_total Total number of users with recovery token');
        $output->writeln('# TYPE userli_users_recovery_token_total gauge');
        $output->writeln('userli_users_recovery_token_total '.$usersRecoveryTokenTotal);

        $usersMailCryptTotal = $this->manager->getRepository(User::class)->countUsersWithMailCrypt();
        $output->writeln('# HELP userli_users_mailcrypt_total Total number of users with enabled mailcrypt');
        $output->writeln('# TYPE userli_users_mailcrypt_total gauge');
        $output->writeln('userli_users_mailcrypt_total '.$usersMailCryptTotal);

        $usersTwofactorTotal = $this->manager->getRepository(User::class)->countUsersWithTwofactor();
        $output->writeln('# HELP userli_users_twofactor_total Total number of users with enabled two factor authentication');
        $output->writeln('# TYPE userli_users_twofactor_total gauge');
        $output->writeln('userli_users_twofactor_total '.$usersTwofactorTotal);

        $redeemedVouchersTotal = $this->manager->getRepository(Voucher::class)->countRedeemedVouchers();
        $unredeemedVouchersTotal = $this->manager->getRepository(Voucher::class)->countUnredeemedVouchers();
        $output->writeln('# HELP userli_vouchers_total Total number of vouchers');
        $output->writeln('# TYPE userli_vouchers_total gauge');
        $output->writeln('userli_vouchers_total{type="unredeemed"} '.$unredeemedVouchersTotal);
        $output->writeln('userli_vouchers_total{type="redeemed"} '.$redeemedVouchersTotal);

        $domainsTotal = $this->manager->getRepository(Domain::class)->count([]);
        $output->writeln('# HELP userli_domains_total Total number of domains');
        $output->writeln('# TYPE userli_domains_total gauge');
        $output->writeln('userli_domains_total '.$domainsTotal);

        $aliasesTotal = $this->manager->getRepository(Alias::class)->count(['deleted' => false]);
        $output->writeln('# HELP userli_aliases_total Total number of aliases');
        $output->writeln('# TYPE userli_aliases_total gauge');
        $output->writeln('userli_aliases_total '.$aliasesTotal);

        $openPgpKeysTotal = $this->manager->getRepository(OpenPgpKey::class)->countKeys();
        $output->writeln('# HELP userli_openpgpkeys_total Total number of OpenPGP keys');
        $output->writeln('# TYPE userli_openpgpkeys_total gauge');
        $output->writeln('userli_openpgpkeys_total '.$openPgpKeysTotal);

        return 0;
    }
}
