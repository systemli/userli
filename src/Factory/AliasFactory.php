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
    /**
     * @param User $user
     * @param null|string $localPart
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
            $localPart = RandomStringGenerator::generate(24, false);
        }

        $alias->setSource($localPart."@".$domain);

        return $alias;
    }
}
