<?php

namespace App\Creator;

use App\Entity\Alias;
use App\Entity\User;
use App\Event\AliasEvent;
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

        $this->eventDispatcher->dispatch(Events::MAIL_ALIAS_CREATED, new AliasEvent($alias));
        $this->validate($alias, ['Default', 'unique']);
        $this->save($alias);

        return $alias;
    }

    /**
     * @param Alias $alias
     * @return bool
     */
    public function validateUnique(Alias $alias): bool
    {
        try {
            $this->validate($alias, ['Default', 'unique']);
        } catch (ValidationException $e) {
            return false;
        }

        return true;
    }
}
