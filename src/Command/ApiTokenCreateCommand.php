<?php

declare(strict_types=1);

namespace App\Command;

use Exception;
use App\Enum\ApiScope;
use App\Service\ApiTokenManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:api-token:create',
    description: 'Create a new API token with specified name and scopes'
)]
class ApiTokenCreateCommand extends Command
{
    public function __construct(
        private readonly ApiTokenManager $apiTokenManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Name for the API token')
            ->addOption(
                'scopes',
                's',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Scopes for the API token (available: ' . implode(', ', ApiScope::all()) . ')',
                []
            )
            ->addOption(
                'all-scopes',
                'a',
                InputOption::VALUE_NONE,
                'Grant all available scopes to the token'
            )
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command creates a new API token:

<info>php %command.full_name% "My Integration Token" --scopes=keycloak --scopes=dovecot</info>

You can specify multiple scopes:
<info>php %command.full_name% "Multi Scope Token" --scopes=keycloak --scopes=dovecot --scopes=postfix</info>

Or grant all available scopes:
<info>php %command.full_name% "Admin Token" --all-scopes</info>

The generated token will be displayed once and cannot be retrieved again.

Available scopes: %s
HELP,
                implode(', ', ApiScope::all())
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');
        $scopes = $input->getOption('scopes');
        $allScopes = $input->getOption('all-scopes');

        // Validate and prepare scopes
        if ($allScopes) {
            $scopes = ApiScope::all();
            $io->info('Using all available scopes: ' . implode(', ', $scopes));
        } elseif (empty($scopes)) {
            $io->error('You must specify at least one scope or use --all-scopes option.');
            $io->note('Available scopes: ' . implode(', ', ApiScope::all()));
            return Command::FAILURE;
        } else {
            // Validate provided scopes
            $availableScopes = ApiScope::all();
            $invalidScopes = array_diff($scopes, $availableScopes);

            if (!empty($invalidScopes)) {
                $io->error('Invalid scope(s): ' . implode(', ', $invalidScopes));
                $io->note('Available scopes: ' . implode(', ', $availableScopes));
                return Command::FAILURE;
            }
        }

        try {
            // Generate token
            $plainToken = $this->apiTokenManager->generateToken();

            // Create API token
            $apiToken = $this->apiTokenManager->create($plainToken, $name, $scopes);

            $io->success('API token created successfully!');

            // Always display the token (since it can only be shown once)
            $io->warning('SECURITY WARNING: The token below will only be shown once!');
            $io->note('Store this token securely - it cannot be retrieved again.');
            $io->writeln('');
            $io->writeln('<fg=green>Token:</> <comment>' . $plainToken . '</comment>');
            $io->writeln('');

            return Command::SUCCESS;

        } catch (Exception $exception) {
            $io->error('Failed to create API token: ' . $exception->getMessage());
            return Command::FAILURE;
        }
    }
}
