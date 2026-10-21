<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\PruneExpiredAppPasswords;
use App\Repository\AppPasswordRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(sign: true)]
final readonly class PruneExpiredAppPasswordsHandler
{
    public function __construct(
        private AppPasswordRepository $repository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(PruneExpiredAppPasswords $message): void
    {
        $deleted = $this->repository->deleteExpired();

        $this->logger->info('Pruned expired app passwords', ['deleted' => $deleted]);
    }
}
