<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\PruneExpiredWebauthnChallenges;
use App\Repository\WebauthnChallengeRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(sign: true)]
final readonly class PruneExpiredWebauthnChallengesHandler
{
    public function __construct(
        private WebauthnChallengeRepository $repository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(PruneExpiredWebauthnChallenges $message): void
    {
        $deleted = $this->repository->deleteExpired();

        $this->logger->info('Pruned expired WebAuthn challenges', ['deleted' => $deleted]);
    }
}
