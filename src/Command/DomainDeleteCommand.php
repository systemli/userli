<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\DomainRepository;
use App\Service\DomainManager;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:domain:delete',
    description: 'Delete a domain and all associated data (users, aliases, vouchers)'
)]
final class DomainDeleteCommand extends Command
{
    public function __construct(
        private readonly DomainRepository $repository,
        private readonly DomainManager $manager,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->addOption('domain', 'd', InputOption::VALUE_REQUIRED, 'The domain name to delete')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be deleted without actually deleting');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $domainName = $input->getOption('domain');
        if (empty($domainName)) {
            $io->error('Please provide a domain name with --domain.');

            return Command::FAILURE;
        }

        $domain = $this->repository->findByName($domainName);
        if (null === $domain) {
            $io->error(sprintf("Domain '%s' not found.", $domainName));

            return Command::FAILURE;
        }

        $stats = $this->manager->getDomainStats($domain);

        if ($input->getOption('dry-run')) {
            $io->note(sprintf("Would delete domain '%s' with:", $domainName));
            $io->listing([
                sprintf('%d users', $stats['users']),
                sprintf('%d aliases', $stats['aliases']),
                sprintf('%d vouchers', $stats['vouchers']),
            ]);

            return Command::SUCCESS;
        }

        $this->manager->delete($domain);
        $io->success(sprintf("Domain '%s' and all associated data have been deleted.", $domainName));

        return Command::SUCCESS;
    }
}
