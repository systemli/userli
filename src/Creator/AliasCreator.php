<?php

declare(strict_types=1);

namespace App\Creator;

use App\Entity\Alias;
use App\Entity\User;
use App\Event\AliasEvent;
use App\Exception\ValidationException;
use App\Factory\AliasFactory;

/**
 * Class AliasCreator.
 */
final class AliasCreator extends AbstractCreator
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

        $this->eventDispatcher->dispatch(new AliasEvent($alias), AliasEvent::CUSTOM_CREATED);
        if (null === $localPart) {
            $this->eventDispatcher->dispatch(new AliasEvent($alias), AliasEvent::RANDOM_CREATED);
        }

        return $alias;
    }
}
