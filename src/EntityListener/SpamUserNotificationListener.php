<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\User;
use App\Enum\Roles;
use App\Message\SpamUserNotification;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: User::class)]
final readonly class SpamUserNotificationListener
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    public function preUpdate(User $user, PreUpdateEventArgs $args): void
    {
        $changes = $args->getEntityChangeSet();
        if (!array_key_exists('roles', $changes)) {
            return;
        }

        [$rolesBefore, $rolesAfter] = $changes['roles'];

        // If `ROLE_SPAM` is added, inform the user about the restriction
        if (!in_array(Roles::SPAM, $rolesBefore, true)
            && in_array(Roles::SPAM, $rolesAfter, true)) {
            $this->bus->dispatch(new SpamUserNotification($user->getId()));
        }
    }
}
