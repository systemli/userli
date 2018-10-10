<?php

namespace App\Creator;

use App\Entity\Alias;
use App\Entity\User;
use App\Event\AliasCreatedEvent;
use App\Event\Events;
use App\Exception\ValidationException;
use App\Factory\AliasFactory;

/**
 * Class AliasCreator.
 */
class AliasCreator extends AbstractCreator
{
    /**
     * @param User $user
     * @param null|string $localPart
     * @return Alias
     * @throws \App\Exception\ValidationException
     */
    public function create(User $user, ?string $localPart): Alias
    {
        $alias = AliasFactory::create($user, $localPart);

        $this->eventDispatcher->dispatch(AliasCreatedEvent::NAME, new AliasCreatedEvent($alias));

        $this->validate($alias, ['Default', 'unique']);
        $this->save($alias);

        return $alias;
    }
}
