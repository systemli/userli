<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\OpenPgpKey;
use App\Message\InvalidateOpenPgpKeyCache;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: OpenPgpKey::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: OpenPgpKey::class)]
#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: OpenPgpKey::class)]
final readonly class InvalidateOpenPgpKeyCacheListener
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    public function postPersist(OpenPgpKey $openPgpKey): void
    {
        $this->invalidate($openPgpKey);
    }

    public function postUpdate(OpenPgpKey $openPgpKey): void
    {
        $this->invalidate($openPgpKey);
    }

    public function postRemove(OpenPgpKey $openPgpKey): void
    {
        $this->invalidate($openPgpKey);
    }

    private function invalidate(OpenPgpKey $openPgpKey): void
    {
        $this->bus->dispatch(new InvalidateOpenPgpKeyCache($openPgpKey->getEmail()));
    }
}
