<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\DomainEvent;
use App\Message\ClearCache;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class DomainListener
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    #[AsEventListener(event: DomainEvent::DELETED)]
    public function onDomainDeleted(DomainEvent $event): void
    {
        $this->bus->dispatch(new ClearCache());
    }
}
