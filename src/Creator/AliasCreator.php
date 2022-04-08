<?php

namespace App\Creator;

use App\Entity\Alias;
use App\Entity\User;
use App\Event\AliasCreatedEvent;
use App\Event\RandomAliasCreatedEvent;
use App\Exception\ValidationException;
use App\Factory\AliasFactory;

/**
 * Class AliasCreator.
 */
class AliasCreator extends AbstractCreator
{
    /**
     * @throws ValidationException
     */
    public function create(User $user, ?string $localPart): Alias
    {
        $localPart = (isset($localPart)) ? strtolower($localPart) : null;
        $alias = AliasFactory::create($user, $localPart);

        $this->validate($alias, ['Default', 'unique']);
        $this->save($alias);

        $this->eventDispatcher->dispatch(new AliasCreatedEvent($alias), AliasCreatedEvent::NAME);
        if (null === $localPart) {
            $this->eventDispatcher->dispatch(new RandomAliasCreatedEvent($alias), RandomAliasCreatedEvent::NAME);
        }

        return $alias;
    }
}
