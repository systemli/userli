<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Message\CreatePostmasterAlias;
use App\Repository\DomainRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreatePostmasterAliasHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DomainRepository $domainRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreatePostmasterAlias $message): void
    {
        $domain = $this->entityManager->getRepository(Domain::class)->find($message->domainId);

        if (null === $domain) {
            $this->logger->warning('Domain not found for postmaster alias creation', ['domainId' => $message->domainId]);

            return;
        }

        $defaultDomain = $this->domainRepository->getDefaultDomain();
        $adminAddress = 'postmaster@'.$defaultDomain;

        if ($domain === $defaultDomain) {
            return;
        }

        $alias = new Alias();
        $alias->setDomain($domain);
        $alias->setSource('postmaster@'.$domain);
        $alias->setDestination($adminAddress);

        $this->entityManager->persist($alias);
        $this->entityManager->flush();

        $this->logger->info('Created postmaster alias', ['domain' => (string) $domain->getName()]);
    }
}
