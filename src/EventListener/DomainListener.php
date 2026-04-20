<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\DomainEvent;
use App\Message\ClearCache;
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
            DomainEvent::DELETED => 'onDomainDeleted',
        ];
    }

    public function onDomainDeleted(DomainEvent $event): void
    {
        $this->bus->dispatch(new ClearCache());
    }
}
