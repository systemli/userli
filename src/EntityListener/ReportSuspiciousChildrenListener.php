<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\User;
use App\Enum\Roles;
use App\Message\ReportSuspiciousChildren;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: User::class)]
final readonly class ReportSuspiciousChildrenListener
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

        // If `ROLE_SUSPICIOUS` is added, report invited users (children) of this user
        if (!in_array(Roles::SUSPICIOUS, $rolesBefore, true)
            && in_array(Roles::SUSPICIOUS, $rolesAfter, true)) {
            $this->bus->dispatch(new ReportSuspiciousChildren($user->getId()));
        }
    }
}
