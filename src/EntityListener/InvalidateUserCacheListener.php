<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\User;
use App\Message\InvalidateUserCache;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEntityListener(event: Events::postPersist, method: 'invalidate', entity: User::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'invalidate', entity: User::class)]
final readonly class InvalidateUserCacheListener
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    public function invalidate(User $user): void
    {
        $this->bus->dispatch(new InvalidateUserCache($user->getEmail()));
    }
}
