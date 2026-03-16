<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ApiTokenManager;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:api-token:delete',
    description: 'Delete an API token by its plain token'
)]
final readonly class ApiTokenDeleteCommand
{
    public function __construct(
        private ApiTokenManager $apiTokenManager,
    ) {
    }

    public function __invoke(
        #[Option(description: 'The plain API token to delete', shortcut: 't')]
        ?string $token = null,
        ?SymfonyStyle $io = null,
    ): int {
        try {
            $plainToken = (string) $token;
            $apiToken = $this->apiTokenManager->findOne($plainToken);

            if ($apiToken === null) {
                $io->error('API token not found.');

                return Command::FAILURE;
            }

            $this->apiTokenManager->delete($apiToken);

            $io->success('API token deleted successfully.');
        } catch (Exception $exception) {
            $io->error('Failed to delete API token: '.$exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
