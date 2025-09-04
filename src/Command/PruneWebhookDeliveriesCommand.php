<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\WebhookDelivery;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:webhooks:prune', description: 'Delete webhook deliveries.html.twig older than 14 days')]
final class PruneWebhookDeliveriesCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $before = (new DateTimeImmutable('-14 days'));

        $qb = $this->em->createQueryBuilder()
            ->delete(WebhookDelivery::class, 'd')
            ->where('d.dispatchedTime < :before')
            ->setParameter('before', $before);

        $deleted = $qb->getQuery()->execute();
        $output->writeln('Deleted: ' . $deleted);

        return Command::SUCCESS;
    }
}
