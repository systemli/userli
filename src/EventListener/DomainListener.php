<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\DomainEvent;
use App\Message\ClearCache;
use App\Message\CreatePostmasterAlias;
use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class DomainListener implements EventSubscriberInterface
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            DomainEvent::CREATED => 'onDomainCreated',
            DomainEvent::DELETED => 'onDomainDeleted',
        ];
    }

    public function onDomainCreated(DomainEvent $event): void
    {
        $this->bus->dispatch(new CreatePostmasterAlias($event->getDomain()->getId()));
    }

    public function onDomainDeleted(DomainEvent $event): void
    {
        $this->bus->dispatch(new ClearCache());
    }
}
