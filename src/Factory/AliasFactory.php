<?php

namespace App\Factory;

use App\Entity\User;
use App\Entity\Alias;
use App\Helper\RandomStringGenerator;

/**
 * Interface AliasFactory.
 */
class AliasFactory
{
    const RANDOM_ALIAS_LENGTH = 24;

    /**
     * @param User        $user
     * @param null|string $localPart
     *
     * @return Alias
     */
    public static function create(User $user, ?string $localPart): Alias
    {
        $domain = $user->getDomain();
        $alias = new Alias();
        $alias->setUser($user);
        $alias->setDomain($domain);
        $alias->setDestination($user->getEmail());
        if (null === $localPart) {
            $localPart = RandomStringGenerator::generate(self::RANDOM_ALIAS_LENGTH, false);
            $alias->setRandom(true);
        }

        $alias->setSource($localPart.'@'.$domain->getName());

        return $alias;
    }
}
