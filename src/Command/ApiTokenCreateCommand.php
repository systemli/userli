<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\ApiScope;
use App\Form\Model\ApiToken as ApiTokenModel;
use App\Service\ApiTokenManager;
use Exception;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:api-token:create',
    description: 'Create a new API token with specified name and scopes'
)]
final class ApiTokenCreateCommand extends Command
{
    public function __construct(
        private readonly ApiTokenManager $apiTokenManager,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->addOption('name', 't', InputOption::VALUE_REQUIRED, 'Name for the API token')
            ->addOption(
                'scopes',
                's',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Scopes for the API token (available: '.implode(', ', ApiScope::all()).')',
                []
            );
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = (string) $input->getOption('name');
        $scopes = (array) $input->getOption('scopes');

        $model = new ApiTokenModel();
        $model->setName($name);
        $model->setScopes($scopes);

        $violations = $this->validator->validate($model);
        if (count($violations) > 0) {
            $io->error('Validation failed:');
            foreach ($violations as $violation) {
                $io->writeln(sprintf(' - %s: %s', $violation->getPropertyPath(), $violation->getMessage()));
            }

            return Command::FAILURE;
        }

        try {
            $plainToken = $this->apiTokenManager->generateToken();

            $this->apiTokenManager->create($plainToken, $name, $scopes);

            $io->info('Store this token securely - it cannot be retrieved again.');
            $io->writeln('');
            $io->writeln('<fg=green>Token:</> <comment>'.$plainToken.'</comment>');
            $io->writeln('');
        } catch (Exception $exception) {
            $io->error('Failed to create API token: '.$exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
