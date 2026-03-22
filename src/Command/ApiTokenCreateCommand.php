<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\ApiScope;
use App\Form\Model\ApiToken as ApiTokenModel;
use App\Service\ApiTokenManager;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:api-token:create',
    description: 'Create a new API token with specified name and scopes'
)]
final readonly class ApiTokenCreateCommand
{
    public function __construct(
        private ApiTokenManager $apiTokenManager,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(
        #[Option(description: 'Name for the API token', shortcut: 't')]
        string $name = '',
        #[Option(description: 'Scopes for the API token (available: '.ApiScope::ALL_SCOPES_DESCRIPTION.')', shortcut: 's')]
        array $scopes = [],
        ?SymfonyStyle $io = null,
    ): int {
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
