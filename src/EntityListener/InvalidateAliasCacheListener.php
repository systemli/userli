<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\Alias;
use App\Message\InvalidateAliasCache;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Alias::class)]
final readonly class InvalidateAliasCacheListener
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    public function postPersist(Alias $alias): void
    {
        $this->bus->dispatch(new InvalidateAliasCache($alias->getSource()));
    }
}
