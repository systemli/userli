<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\DomainRepository;
use App\Service\DomainManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:domain:delete',
    description: 'Delete a domain and all associated data (users, aliases, vouchers)'
)]
final readonly class DomainDeleteCommand
{
    public function __construct(
        private DomainRepository $repository,
        private DomainManager $manager,
    ) {
    }

    public function __invoke(
        #[Option(name: 'domain', description: 'The domain name to delete', shortcut: 'd')]
        ?string $domainName = null,
        #[Option(name: 'dry-run', description: 'Show what would be deleted without actually deleting')]
        bool $dryRun = false,
        ?SymfonyStyle $io = null,
    ): int {
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

        if ($dryRun) {
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
