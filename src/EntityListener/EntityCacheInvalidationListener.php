<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Service\Cache\EntityCacheInvalidator;
use App\Service\Cache\EntityCacheType;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, method: 'onUserChange', entity: User::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onUserChange', entity: User::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onAliasChange', entity: Alias::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onAliasChange', entity: Alias::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onDomainChange', entity: Domain::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onOpenPgpKeyChange', entity: OpenPgpKey::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onOpenPgpKeyChange', entity: OpenPgpKey::class)]
#[AsEntityListener(event: Events::postRemove, method: 'onOpenPgpKeyChange', entity: OpenPgpKey::class)]
final readonly class EntityCacheInvalidationListener
{
    public function __construct(private EntityCacheInvalidator $invalidator)
    {
    }

    public function onUserChange(User $user): void
    {
        $this->invalidator->dispatch(EntityCacheType::USER, $user->getEmail());
    }

    public function onAliasChange(Alias $alias): void
    {
        $this->invalidator->dispatch(EntityCacheType::ALIAS, $alias->getSource());
    }

    public function onDomainChange(Domain $domain): void
    {
        $this->invalidator->dispatch(EntityCacheType::DOMAIN, $domain->getName());
    }

    public function onOpenPgpKeyChange(OpenPgpKey $openPgpKey): void
    {
        $this->invalidator->dispatch(EntityCacheType::OPENPGP_KEY, $openPgpKey->getEmail());
    }
}
