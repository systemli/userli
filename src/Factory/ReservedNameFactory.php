<?php

namespace App\Factory;

use App\Entity\ReservedName;

/**
 * Interface ReservedNameFactory.
 */
class ReservedNameFactory
{
    /**
     * @param string $name
     * @return ReservedName
     */
    public static function create(string $name): ReservedName
    {
        $reservedName = new ReservedName();
        $reservedName->setName($name);

        return $reservedName;
    }
}
