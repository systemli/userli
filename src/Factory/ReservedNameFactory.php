<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\ReservedName;

/**
 * Interface ReservedNameFactory.
 */
class ReservedNameFactory
{
    public static function create(string $name): ReservedName
    {
        $reservedName = new ReservedName();
        $reservedName->setName($name);

        return $reservedName;
    }
}
