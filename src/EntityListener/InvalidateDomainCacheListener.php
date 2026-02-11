<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\Domain;
use App\Message\InvalidateDomainCache;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Domain::class)]
final readonly class InvalidateDomainCacheListener
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    public function postPersist(Domain $domain): void
    {
        $this->bus->dispatch(new InvalidateDomainCache($domain->getName()));
    }
}
