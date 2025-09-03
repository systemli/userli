<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ApiTokenManager;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:api-token:delete',
    description: 'Delete an API token by its plain token'
)]
class ApiTokenDeleteCommand extends Command
{
    public function __construct(
        private readonly ApiTokenManager $apiTokenManager
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('token', 't', InputOption::VALUE_REQUIRED, 'The plain API token to delete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $plainToken = (string)$input->getOption('token');
            $apiToken = $this->apiTokenManager->findOne($plainToken);

            if ($apiToken === null) {
                $io->error('API token not found.');
                return Command::FAILURE;
            }

            $this->apiTokenManager->delete($apiToken);

            $io->success('API token deleted successfully.');
        } catch (Exception $exception) {
            $io->error('Failed to delete API token: ' . $exception->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
